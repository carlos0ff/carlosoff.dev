<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Situação do relacionamento com o cliente.
 */
enum ClientStatus: string
{
    use HasOptions;

    case Prospect = 'prospect';
    case Active = 'active';
    case Inactive = 'inactive';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospecção',
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Ended => 'Encerrado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Prospect => 'muted',
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Ended => 'muted',
        };
    }
}
