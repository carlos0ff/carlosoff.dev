<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Estado contratual.
 */
enum ContractStatus: string
{
    use HasOptions;

    case None = 'none';
    case Draft = 'draft';
    case AwaitingSignature = 'awaiting_signature';
    case Active = 'active';
    case Suspended = 'suspended';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sem contrato',
            self::Draft => 'Minuta',
            self::AwaitingSignature => 'Aguardando assinatura',
            self::Active => 'Vigente',
            self::Suspended => 'Suspenso',
            self::Ended => 'Encerrado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::None => 'muted',
            self::Draft => 'muted',
            self::AwaitingSignature => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Ended => 'muted',
            self::Cancelled => 'muted',
        };
    }
}
