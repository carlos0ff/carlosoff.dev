<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Situação da cobrança. 'Criada' não é 'recebida': só pagamentos confirmados contam como recebidos.
 */
enum ChargeStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::PartiallyPaid => 'Parcialmente paga',
            self::Paid => 'Paga',
            self::Overdue => 'Vencida',
            self::Cancelled => 'Cancelada',
            self::Refunded => 'Estornada',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::PartiallyPaid => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'muted',
            self::Refunded => 'muted',
        };
    }
}
