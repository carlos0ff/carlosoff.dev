<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Tipos de documento gerados por projeto.
 */
enum DocumentType: string
{
    use HasOptions;

    case DevelopmentContract = 'development_contract';
    case AcceptanceTerm = 'acceptance_term';
    case CommercialTerms = 'commercial_terms';
    case DomainHosting = 'domain_hosting';
    case SupportMaintenance = 'support_maintenance';
    case ScopeChange = 'scope_change';
    case Closure = 'closure';

    public function label(): string
    {
        return match ($this) {
            self::DevelopmentContract => 'Contrato de desenvolvimento de software',
            self::AcceptanceTerm => 'Termo de validação e aceite',
            self::CommercialTerms => 'Termo de valores e condições comerciais',
            self::DomainHosting => 'Termo de domínio e hospedagem',
            self::SupportMaintenance => 'Termo de suporte e manutenção',
            self::ScopeChange => 'Termo de solicitação fora do escopo',
            self::Closure => 'Termo de encerramento e entrega',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::DevelopmentContract => 'primary',
            self::AcceptanceTerm => 'success',
            self::CommercialTerms => 'warning',
            self::DomainHosting => 'info',
            self::SupportMaintenance => 'info',
            self::ScopeChange => 'warning',
            self::Closure => 'muted',
        };
    }
}
