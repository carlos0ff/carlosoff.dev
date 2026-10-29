<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Prioridade de chamado.
 */
enum TicketPriority: string
{
    use HasOptions;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Normal => 'Normal',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'muted',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
