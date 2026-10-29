<?php

declare(strict_types=1);

namespace CentralAgent;

use RuntimeException;

/**
 * Agente de referência instalado em cada projeto de cliente (PHP 8.1+, sem dependências).
 *
 * - Modelo pull: o agente consulta o painel central; nenhuma porta precisa ser aberta no servidor do cliente.
 * - Toda requisição é assinada (HMAC-SHA256 + timestamp + nonce); toda resposta de comandos é verificada.
 * - Executa somente comandos autenticados E permitidos pela política local (ALLOWED_COMMANDS / ALLOWED_MODES).
 * - Restrições apenas alternam um arquivo de estado lido pelo MaintenanceGate: nada apaga dados, nada mexe
 *   em backups, nada degrada desempenho. Tudo é reversível com um comando de reativação.
 * - Se o painel central estiver indisponível, o estado atual é mantido (falha nunca gera suspensão).
 */
final class CentralAgent
{
    public const VERSION = '1.0.0';

    private const CLOCK_SKEW = 300;

    /**
     * @param  list<string>  $allowedCommands
     * @param  list<string>  $allowedModes
     */
    public function __construct(
        private string $apiUrl,
        private string $projectId,
        private string $keyId,
        private string $secret,
        private string $stateDir,
        private array $allowedCommands = ['maintenance', 'suspend', 'reactivate'],
        private array $allowedModes = ['maintenance_page', 'restrict_non_essential'],
        private ?string $deployedVersion = null,
        private ?string $backupMarker = null,
        private int $timeout = 15,
    ) {
        if (! str_starts_with($apiUrl, 'https://') && getenv('AGENT_ALLOW_INSECURE') !== '1') {
            throw new RuntimeException('CENTRAL_API_URL precisa usar HTTPS (AGENT_ALLOW_INSECURE=1 apenas em ambiente local).');
        }
    }

    public static function fromEnv(): self
    {
        $required = ['CENTRAL_API_URL', 'CENTRAL_PROJECT_ID', 'CENTRAL_KEY_ID', 'CENTRAL_SECRET'];
        foreach ($required as $name) {
            if (getenv($name) === false || getenv($name) === '') {
                throw new RuntimeException("Variável de ambiente ausente: {$name}");
            }
        }

        $list = fn (string $name, string $default) => array_values(array_filter(array_map('trim', explode(',', getenv($name) ?: $default))));

        return new self(
            rtrim((string) getenv('CENTRAL_API_URL'), '/'),
            (string) getenv('CENTRAL_PROJECT_ID'),
            (string) getenv('CENTRAL_KEY_ID'),
            (string) getenv('CENTRAL_SECRET'),
            getenv('AGENT_STATE_DIR') ?: sys_get_temp_dir().'/central-agent',
            $list('AGENT_ALLOWED_COMMANDS', 'maintenance,suspend,reactivate'),
            $list('AGENT_ALLOWED_MODES', 'maintenance_page,restrict_non_essential'),
            getenv('AGENT_DEPLOYED_VERSION') ?: null,
            getenv('AGENT_BACKUP_MARKER') ?: null,
        );
    }

    /**
     * Busca comandos pendentes, valida e aplica conforme a política local, e confirma (ack).
     *
     * @return list<array{id: string, type: string, result: string, message: string}>
     */
    public function sync(): array
    {
        $response = $this->request('GET', "/projects/{$this->projectId}/commands/pending");
        $this->assertSignedResponse($response);

        $results = [];
        foreach ($response['json']['data'] ?? [] as $command) {
            [$status, $message] = $this->apply($command);
            $this->request('POST', "/projects/{$this->projectId}/commands/{$command['id']}/ack", ['status' => $status, 'message' => $message]);
            $results[] = ['id' => $command['id'], 'type' => $command['type'], 'result' => $status, 'message' => $message];
        }

        return $results;
    }

    /**
     * Envia o heartbeat: estado local, versão, último backup e dependências (sem segredos nem dados pessoais).
     *
     * @param  list<array{name: string, ok: bool, latency_ms?: int}>  $dependencies
     */
    public function heartbeat(array $dependencies = []): void
    {
        $state = State::load($this->stateDir);
        $disk = @disk_free_space('/') !== false && @disk_total_space('/') ? round(100 - (disk_free_space('/') / disk_total_space('/') * 100), 1) : null;

        $this->request('POST', "/projects/{$this->projectId}/health", array_filter([
            'state' => $state->effectiveMode(),
            'deployed_version' => $this->deployedVersion,
            'agent_version' => self::VERSION,
            'php_version' => PHP_VERSION,
            'last_backup_at' => $this->backupMarker && is_file($this->backupMarker) ? date(DATE_ATOM, (int) filemtime($this->backupMarker)) : null,
            'resources' => array_filter(['cpu_load' => function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? null) : null, 'disk_used_pct' => $disk], fn ($v) => $v !== null),
            'dependencies' => $dependencies,
        ], fn ($v) => $v !== null && $v !== []));
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return $this->request('GET', "/projects/{$this->projectId}/status")['json']['data'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $command
     * @return array{0: string, 1: string}
     */
    private function apply(array $command): array
    {
        $type = (string) ($command['type'] ?? '');
        $mode = $command['mode'] ?? null;

        if (! in_array($type, $this->allowedCommands, true)) {
            return ['rejected', "Comando '{$type}' não permitido pela política desta instalação."];
        }

        if ($type !== 'reactivate' && ! in_array($mode, $this->allowedModes, true)) {
            return ['rejected', "Modo '{$mode}' não permitido pela política desta instalação."];
        }

        $state = State::load($this->stateDir);

        if ($state->commandId === $command['id']) {
            return ['executed', 'Comando já aplicado anteriormente (idempotente).'];
        }

        $new = match ($type) {
            'maintenance' => new State('maintenance', $command['message'] ?? null, $command['starts_at'] ?? null, $command['ends_at'] ?? null, $command['id']),
            'suspend' => new State($mode === 'restrict_non_essential' ? 'restricted' : 'suspended', $command['message'] ?? null, null, null, $command['id']),
            'reactivate' => new State('active', null, null, null, $command['id']),
        };

        $new->save($this->stateDir);

        return ['executed', "Estado local: {$new->mode}."];
    }

    /**
     * @param  array{status: int, json: array<string, mixed>|null, body: string, headers: array<string, string>}  $response
     */
    private function assertSignedResponse(array $response): void
    {
        $timestamp = $response['headers']['x-response-timestamp'] ?? '';
        $signature = $response['headers']['x-response-signature'] ?? '';
        $expected = hash_hmac('sha256', $timestamp."\n".hash('sha256', $response['body']), $this->secret);

        if ($timestamp === '' || abs(time() - (int) $timestamp) > self::CLOCK_SKEW || ! hash_equals($expected, $signature)) {
            throw new RuntimeException('Resposta do painel central sem assinatura válida: nenhum comando foi executado.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{status: int, json: array<string, mixed>|null, body: string, headers: array<string, string>}
     */
    private function request(string $method, string $path, ?array $payload = null, int $attempt = 1): array
    {
        $body = $payload === null ? '' : (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $url = $this->apiUrl.$path;
        $uriPath = (string) parse_url($url, PHP_URL_PATH).(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : '');
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = implode("\n", [$method, $uriPath, $timestamp, $nonce, hash('sha256', $body)]);

        $headers = [];
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POSTFIELDS => $body === '' ? null : $body,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Api-Key: '.$this->keyId,
                'X-Timestamp: '.$timestamp,
                'X-Nonce: '.$nonce,
                'X-Signature: '.hash_hmac('sha256', $canonical, $this->secret),
                'User-Agent: CentralAgent/'.self::VERSION,
            ],
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$headers): int {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }

                return strlen($line);
            },
        ]);

        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Retries só para falhas transitórias (rede, 429, 5xx), com backoff exponencial.
        if (($raw === false || $status === 429 || $status >= 500) && $attempt < 3) {
            usleep((2 ** $attempt) * 250_000);

            return $this->request($method, $path, $payload, $attempt + 1);
        }

        if ($raw === false) {
            throw new RuntimeException("Painel central indisponível: {$error}");
        }

        $json = json_decode((string) $raw, true);
        if ($status >= 400) {
            throw new RuntimeException("Painel central respondeu HTTP {$status}: ".($json['error']['message'] ?? substr((string) $raw, 0, 200)));
        }

        return ['status' => $status, 'json' => is_array($json) ? $json : null, 'body' => (string) $raw, 'headers' => $headers];
    }
}
