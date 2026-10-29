<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Situação de um signatário.
 */
enum SignerStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Signed = 'signed';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Signed => 'Assinou',
            self::Refused => 'Recusou',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Signed => 'success',
            self::Refused => 'danger',
        };
    }
}
