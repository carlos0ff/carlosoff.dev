<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Tipo de credencial da API.
 */
enum CredentialKind: string
{
    use HasOptions;

    case Installation = 'installation';
    case Automation = 'automation';

    public function label(): string
    {
        return match ($this) {
            self::Installation => 'Instalação (agente do projeto)',
            self::Automation => 'Automação administrativa',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Installation => 'info',
            self::Automation => 'primary',
        };
    }
}
