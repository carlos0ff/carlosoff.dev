<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Forma de restrição permitida pela política contratual. Nenhuma degrada desempenho ou toca em dados.
 */
enum RestrictionMode: string
{
    use HasOptions;

    case None = 'none';
    case BlockNewRequests = 'block_new_requests';
    case RestrictNonEssential = 'restrict_non_essential';
    case MaintenancePage = 'maintenance_page';
    case SuspendHosting = 'suspend_hosting';
    case SuspendAdditionalServices = 'suspend_additional_services';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nenhuma restrição',
            self::BlockNewRequests => 'Bloquear novas solicitações não incluídas',
            self::RestrictNonEssential => 'Restringir funcionalidades não essenciais',
            self::MaintenancePage => 'Página de manutenção administrativa transparente',
            self::SuspendHosting => 'Suspender hospedagem administrada por nós',
            self::SuspendAdditionalServices => 'Suspender serviços adicionais prestados por nós',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::None => 'muted',
            self::BlockNewRequests => 'warning',
            self::RestrictNonEssential => 'warning',
            self::MaintenancePage => 'danger',
            self::SuspendHosting => 'danger',
            self::SuspendAdditionalServices => 'warning',
        };
    }
}
