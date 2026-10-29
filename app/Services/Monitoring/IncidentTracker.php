<?php

namespace App\Services\Monitoring;

use App\Enums\StaffRole;
use App\Models\ClientProject;
use App\Models\HealthCheck;
use App\Models\MonitoringIncident;
use App\Services\Notifier;

/**
 * Abre/fecha incidentes técnicos. Isolado do financeiro: nada aqui gera cobrança, aviso financeiro ou suspensão.
 */
class IncidentTracker
{
    public function __construct(protected Notifier $notifier) {}

    public function evaluate(ClientProject $project, string $source): void
    {
        $threshold = (int) config('gestao.monitoring.incident_after_failures');
        $recent = HealthCheck::where('client_project_id', $project->id)->where('source', $source)->latest('checked_at')->latest('id')->limit($threshold)->get();
        $open = MonitoringIncident::where('client_project_id', $project->id)->where('kind', $source)->whereNull('resolved_at')->first();

        if ($recent->first()?->is_up && $open) {
            $open->update(['resolved_at' => now()]);

            return;
        }

        if (! $open && $recent->count() >= $threshold && $recent->every(fn (HealthCheck $check) => ! $check->is_up)) {
            $incident = MonitoringIncident::create([
                'client_project_id' => $project->id,
                'kind' => $source,
                'summary' => $recent->first()->error ?? 'Falhas consecutivas de verificação',
                'opened_at' => now(),
            ]);

            $this->notifier->staff("Incidente técnico: {$project->name}", $incident->summary, route('admin.gestao.monitoring.index'), 'monitoramento', [StaffRole::Admin, StaffRole::Technical]);
        }
    }
}
