<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Papel do signatário.
 */
enum SignerRole: string
{
    use HasOptions;

    case Contractor = 'contractor';
    case Client = 'client';
    case Witness = 'witness';

    public function label(): string
    {
        return match ($this) {
            self::Contractor => 'Contratada',
            self::Client => 'Contratante',
            self::Witness => 'Testemunha',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Contractor => 'primary',
            self::Client => 'info',
            self::Witness => 'muted',
        };
    }
}
