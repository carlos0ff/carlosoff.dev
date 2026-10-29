<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Estado do desenvolvimento (independente do financeiro e do operacional).
 */
enum DevelopmentStatus: string
{
    use HasOptions;

    case Planning = 'planning';
    case Development = 'development';
    case Staging = 'staging';
    case AwaitingApproval = 'awaiting_approval';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planejamento',
            self::Development => 'Desenvolvimento',
            self::Staging => 'Homologação',
            self::AwaitingApproval => 'Aguardando aprovação',
            self::Completed => 'Concluído',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planning => 'muted',
            self::Development => 'info',
            self::Staging => 'info',
            self::AwaitingApproval => 'warning',
            self::Completed => 'success',
        };
    }
}
