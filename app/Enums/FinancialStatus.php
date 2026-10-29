<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Estado financeiro, calculado a partir das cobranças (nunca informado pelo cliente).
 */
enum FinancialStatus: string
{
    use HasOptions;

    case UpToDate = 'up_to_date';
    case Pending = 'pending';
    case Overdue = 'overdue';
    case Negotiating = 'negotiating';
    case Regularized = 'regularized';

    public function label(): string
    {
        return match ($this) {
            self::UpToDate => 'Em dia',
            self::Pending => 'Pendente',
            self::Overdue => 'Vencido',
            self::Negotiating => 'Em negociação',
            self::Regularized => 'Regularizado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::UpToDate => 'success',
            self::Pending => 'warning',
            self::Overdue => 'danger',
            self::Negotiating => 'info',
            self::Regularized => 'success',
        };
    }
}
