<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Papel do usuário. Equipe acessa /admin; cliente acessa somente /portal.
 */
enum StaffRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Finance = 'financeiro';
    case Technical = 'tecnico';
    case Client = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Finance => 'Financeiro',
            self::Technical => 'Técnico',
            self::Client => 'Cliente (portal)',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Finance => 'info',
            self::Technical => 'info',
            self::Client => 'muted',
        };
    }
}
