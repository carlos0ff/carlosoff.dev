<?php

declare(strict_types=1);

namespace CentralAgent;

/**
 * Estado local do projeto (arquivo JSON gravado de forma atômica). Ler o estado nunca depende da rede.
 */
final class State
{
    public function __construct(
        public string $mode = 'active',
        public ?string $message = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?string $commandId = null,
        public ?string $appliedAt = null,
    ) {
        $this->appliedAt ??= date(DATE_ATOM);
    }

    public static function load(string $dir): self
    {
        $file = $dir.'/state.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (! is_array($data)) {
            return new self;
        }

        return new self($data['mode'] ?? 'active', $data['message'] ?? null, $data['starts_at'] ?? null, $data['ends_at'] ?? null, $data['command_id'] ?? null, $data['applied_at'] ?? null);
    }

    public function save(string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0o750, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Não foi possível criar {$dir}");
        }

        $tmp = tempnam($dir, 'state');
        file_put_contents($tmp, json_encode([
            'mode' => $this->mode,
            'message' => $this->message,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'command_id' => $this->commandId,
            'applied_at' => $this->appliedAt,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        chmod($tmp, 0o640);
        rename($tmp, $dir.'/state.json');
    }

    /**
     * Manutenção programada respeita a janela (início/fim) localmente, mesmo sem contato com o painel.
     */
    public function effectiveMode(?int $now = null): string
    {
        $now ??= time();

        if ($this->mode === 'maintenance') {
            if ($this->startsAt && strtotime($this->startsAt) > $now) {
                return 'active';
            }
            if ($this->endsAt && strtotime($this->endsAt) <= $now) {
                return 'active';
            }
        }

        return $this->mode;
    }

    public function blocksAccess(?int $now = null): bool
    {
        return in_array($this->effectiveMode($now), ['maintenance', 'suspended'], true);
    }

    public function isRestricted(?int $now = null): bool
    {
        return $this->effectiveMode($now) === 'restricted';
    }
}
