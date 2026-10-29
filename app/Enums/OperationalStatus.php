<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Estado operacional do serviço publicado.
 */
enum OperationalStatus: string
{
    use HasOptions;

    case Active = 'active';
    case ScheduledMaintenance = 'scheduled_maintenance';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::ScheduledMaintenance => 'Manutenção programada',
            self::Suspended => 'Suspenso conforme contrato',
            self::Terminated => 'Encerrado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::ScheduledMaintenance => 'info',
            self::Suspended => 'danger',
            self::Terminated => 'muted',
        };
    }
}
