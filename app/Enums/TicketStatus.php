<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Situação de um chamado.
 */
enum TicketStatus: string
{
    use HasOptions;

    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingClient = 'waiting_client';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::InProgress => 'Em atendimento',
            self::WaitingClient => 'Aguardando cliente',
            self::Resolved => 'Resolvido',
            self::Closed => 'Fechado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::InProgress => 'info',
            self::WaitingClient => 'muted',
            self::Resolved => 'success',
            self::Closed => 'muted',
        };
    }
}
