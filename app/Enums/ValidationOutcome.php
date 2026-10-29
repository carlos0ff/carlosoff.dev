<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Resultado de uma validação de versão. Silêncio do cliente não é aceite.
 */
enum ValidationOutcome: string
{
    use HasOptions;

    case Pending = 'pending';
    case Approved = 'approved';
    case ApprovedWithReservations = 'approved_with_reservations';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando cliente',
            self::Approved => 'Aprovado',
            self::ApprovedWithReservations => 'Aprovado com ressalvas',
            self::Rejected => 'Reprovado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::ApprovedWithReservations => 'info',
            self::Rejected => 'danger',
        };
    }
}
