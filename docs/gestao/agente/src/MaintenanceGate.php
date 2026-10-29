<?php

declare(strict_types=1);

namespace CentralAgent;

/**
 * Inclua no topo do front controller (ex.: public/index.php) ANTES de iniciar a aplicação:
 *
 *     require __DIR__.'/../central-agent/src/State.php';
 *     require __DIR__.'/../central-agent/src/MaintenanceGate.php';
 *     \CentralAgent\MaintenanceGate::handle(getenv('AGENT_STATE_DIR') ?: '/var/lib/central-agent');
 *
 * Exibe uma página de manutenção transparente (HTTP 503 + Retry-After). Não altera dados nem backups.
 * Caminhos em AGENT_BYPASS_PATHS (ex.: /up, /webhooks) continuam acessíveis.
 */
final class MaintenanceGate
{
    public static function handle(string $stateDir): void
    {
        $state = State::load($stateDir);
        if (! $state->blocksAccess()) {
            return;
        }

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        foreach (array_filter(explode(',', getenv('AGENT_BYPASS_PATHS') ?: '/up')) as $bypass) {
            if ($bypass !== '' && str_starts_with($path, trim($bypass))) {
                return;
            }
        }

        $title = $state->effectiveMode() === 'maintenance' ? 'Sistema em manutenção' : 'Acesso temporariamente restrito';
        $message = $state->message ?: ($state->effectiveMode() === 'maintenance'
            ? 'Estamos realizando uma manutenção programada. Voltamos em breve.'
            : 'O acesso está temporariamente restrito conforme o contrato de serviço. Os dados estão preservados.');

        http_response_code(503);
        header('Retry-After: 3600');
        header('Cache-Control: no-store');
        header('Content-Type: text/html; charset=UTF-8');

        $timezone = new \DateTimeZone(getenv('AGENT_TIMEZONE') ?: 'America/Sao_Paulo');
        $until = $state->endsAt ? '<p>Previsão de retorno: '.htmlspecialchars((new \DateTimeImmutable($state->endsAt))->setTimezone($timezone)->format('d/m/Y H:i')).'</p>' : '';
        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
            .htmlspecialchars($title).'</title><style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0;padding:1rem}main{max-width:32rem;text-align:center}h1{font-size:1.5rem}p{color:#94a3b8}</style></head><body><main><h1>'
            .htmlspecialchars($title).'</h1><p>'.htmlspecialchars($message).'</p>'.$until.'<p>Se você é o responsável pelo contrato, entre em contato com o suporte.</p></main></body></html>';

        exit;
    }
}
