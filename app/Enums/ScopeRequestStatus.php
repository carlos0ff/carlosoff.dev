<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Solicitação fora do escopo. Só vira 'aprovada' com consentimento registrado do cliente.
 */
enum ScopeRequestStatus: string
{
    use HasOptions;

    case Requested = 'requested';
    case UnderAnalysis = 'under_analysis';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitada',
            self::UnderAnalysis => 'Em análise',
            self::Quoted => 'Orçada (aguardando cliente)',
            self::Approved => 'Aprovada pelo cliente',
            self::Rejected => 'Recusada',
            self::Cancelled => 'Cancelada',
            self::Delivered => 'Entregue',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'muted',
            self::UnderAnalysis => 'info',
            self::Quoted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'muted',
            self::Delivered => 'success',
        };
    }
}
