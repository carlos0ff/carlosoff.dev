<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Função de um contato da empresa cliente.
 */
enum ContactRole: string
{
    use HasOptions;

    case Contract = 'contract';
    case Technical = 'technical';
    case Financial = 'financial';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Responsável pelo contrato',
            self::Technical => 'Técnico',
            self::Financial => 'Financeiro',
            self::Other => 'Outro',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Contract => 'primary',
            self::Technical => 'info',
            self::Financial => 'warning',
            self::Other => 'muted',
        };
    }
}
