<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Ciclo de vida de um comando remoto.
 */
enum CommandStatus: string
{
    use HasOptions;

    case PendingApproval = 'pending_approval';
    case Queued = 'queued';
    case Delivered = 'delivered';
    case Executed = 'executed';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Aguardando aprovação',
            self::Queued => 'Na fila do agente',
            self::Delivered => 'Entregue ao agente',
            self::Executed => 'Executado',
            self::Rejected => 'Recusado pelo agente',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PendingApproval => 'warning',
            self::Queued => 'info',
            self::Delivered => 'info',
            self::Executed => 'success',
            self::Rejected => 'danger',
            self::Failed => 'danger',
            self::Cancelled => 'muted',
        };
    }
}
