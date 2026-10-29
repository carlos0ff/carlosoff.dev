<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Comando remoto enviado ao agente do projeto.
 */
enum CommandType: string
{
    use HasOptions;

    case Maintenance = 'maintenance';
    case Suspend = 'suspend';
    case Reactivate = 'reactivate';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Manutenção programada',
            self::Suspend => 'Suspensão contratual',
            self::Reactivate => 'Reativação',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Maintenance => 'info',
            self::Suspend => 'danger',
            self::Reactivate => 'success',
        };
    }
}
