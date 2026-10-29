<?php

namespace App\Services\Monitoring;

use App\Enums\OperationalStatus;
use App\Models\ClientProject;
use App\Models\HealthCheck;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Recebe o heartbeat do agente. Só chaves de uma lista fechada são gravadas (sem segredos, sem dados pessoais).
 */
class AgentHeartbeat
{
    public function __construct(protected IncidentTracker $incidents) {}

    /**
     * @param  array<string, mixed>  $payload  dados já validados
     */
    public function ingest(ClientProject $project, array $payload): HealthCheck
    {
        $metrics = array_filter([
            'php_version' => isset($payload['php_version']) ? Str::limit((string) $payload['php_version'], 20, '') : null,
            'agent_version' => isset($payload['agent_version']) ? Str::limit((string) $payload['agent_version'], 20, '') : null,
            'agent_state' => $payload['state'] ?? null,
            'cpu_load' => Arr::get($payload, 'resources.cpu_load'),
            'memory_used_pct' => Arr::get($payload, 'resources.memory_used_pct'),
            'disk_used_pct' => Arr::get($payload, 'resources.disk_used_pct'),
            'dependencies' => collect($payload['dependencies'] ?? [])->take(20)->map(fn (array $dependency) => [
                'name' => Str::limit((string) $dependency['name'], 40, ''),
                'ok' => (bool) $dependency['ok'],
                'latency_ms' => isset($dependency['latency_ms']) ? (int) $dependency['latency_ms'] : null,
            ])->values()->all() ?: null,
        ], fn ($value) => $value !== null);

        $dependenciesOk = collect($metrics['dependencies'] ?? [])->every(fn (array $dependency) => $dependency['ok']);

        $check = HealthCheck::create([
            'client_project_id' => $project->id,
            'source' => 'agent',
            'is_up' => $dependenciesOk,
            'deployed_version' => $payload['deployed_version'] ?? null,
            'last_backup_at' => isset($payload['last_backup_at']) ? Carbon::parse($payload['last_backup_at']) : null,
            'app_errors_last_hour' => $payload['app_errors_last_hour'] ?? null,
            'metrics' => $metrics,
            'error' => $dependenciesOk ? null : 'Dependência indisponível: '.collect($metrics['dependencies'])->where('ok', false)->pluck('name')->implode(', '),
            'checked_at' => now(),
        ]);

        $updates = ['agent_last_seen_at' => now()];
        if (! empty($payload['deployed_version'])) {
            $updates['deployed_version'] = $payload['deployed_version'];
        }
        // Janela de manutenção encerrada localmente pelo agente.
        if ($project->operational_status === OperationalStatus::ScheduledMaintenance && ($payload['state'] ?? null) === 'active') {
            $updates['operational_status'] = OperationalStatus::Active;
        }
        $project->forceFill($updates)->saveQuietly();

        $this->incidents->evaluate($project, 'agent');

        return $check;
    }
}
