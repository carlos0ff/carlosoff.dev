<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Ciclo de vida de um documento para assinatura.
 */
enum DocumentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case UnderReview = 'under_review';
    case AwaitingSignature = 'awaiting_signature';
    case PartiallySigned = 'partially_signed';
    case Signed = 'signed';
    case Refused = 'refused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::UnderReview => 'Aguardando revisão',
            self::AwaitingSignature => 'Aguardando assinatura',
            self::PartiallySigned => 'Parcialmente assinado',
            self::Signed => 'Assinado',
            self::Refused => 'Recusado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::UnderReview => 'info',
            self::AwaitingSignature => 'warning',
            self::PartiallySigned => 'warning',
            self::Signed => 'success',
            self::Refused => 'danger',
            self::Cancelled => 'muted',
            self::Expired => 'muted',
        };
    }
}
