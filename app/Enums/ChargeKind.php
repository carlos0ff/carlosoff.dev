<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Natureza da cobrança.
 */
enum ChargeKind: string
{
    use HasOptions;

    case Setup = 'setup';
    case Monthly = 'monthly';
    case Additional = 'additional';
    case Hosting = 'hosting';
    case Domain = 'domain';
    case Support = 'support';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Setup => 'Implantação',
            self::Monthly => 'Mensalidade',
            self::Additional => 'Desenvolvimento adicional',
            self::Hosting => 'Hospedagem',
            self::Domain => 'Domínio',
            self::Support => 'Suporte avulso',
            self::Other => 'Outro',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Setup => 'primary',
            self::Monthly => 'info',
            self::Additional => 'warning',
            self::Hosting => 'info',
            self::Domain => 'info',
            self::Support => 'muted',
            self::Other => 'muted',
        };
    }
}
