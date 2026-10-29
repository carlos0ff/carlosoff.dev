<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Como reativar após a regularização.
 */
enum ReactivationMode: string
{
    use HasOptions;

    case Automatic = 'automatic';
    case ManualApproval = 'manual_approval';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automática após confirmação do pagamento',
            self::ManualApproval => 'Mediante aprovação da equipe',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Automatic => 'success',
            self::ManualApproval => 'warning',
        };
    }
}
