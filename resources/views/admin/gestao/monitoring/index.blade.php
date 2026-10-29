<x-layouts.admin title="Monitoramento" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Monitoramento' => null]">
    <x-admin.page-header title="Monitoramento técnico" subtitle="Disponibilidade, agente, certificados e domínios. Inadimplência não aparece aqui, e falha técnica nunca dispara suspensão." />

    <x-admin.table>
        <thead><tr><th>Projeto</th><th>Externo (HTTP)</th><th>Agente</th><th>Versão</th><th>Último backup</th><th>SSL</th></tr></thead>
        <tbody>
            @forelse ($projects as $row)
                @php([$project, $probe, $agent] = [$row['project'], $row['probe'], $row['agent']])
                <tr>
                    <td data-label="Projeto"><a class="font-semibold hover:text-primary-hover" href="{{ route('admin.gestao.projects.show', $project) }}">{{ $project->name }}</a><span class="block text-xs text-foreground-muted">{{ $project->production_url ?? 'sem URL de produção' }}</span></td>
                    <td data-label="Externo">@if ($probe)<x-admin.badge :tone="$probe->is_up ? 'success' : 'danger'">{{ $probe->is_up ? 'online' : 'falha' }}</x-admin.badge> <span class="text-xs text-foreground-muted">{{ $probe->http_status }} · {{ $probe->response_time_ms }}ms · {{ $probe->checked_at->diffForHumans(short: true) }}</span>@if ($probe->error)<span class="block text-xs text-error">{{ $probe->error }}</span>@endif @else — @endif</td>
                    <td data-label="Agente">@if ($agent)<x-admin.badge :tone="$agent->is_up ? 'success' : 'warning'">{{ $agent->is_up ? 'ok' : 'dependência falhou' }}</x-admin.badge> <span class="text-xs text-foreground-muted">{{ $project->agent_last_seen_at?->diffForHumans(short: true) }}</span>@else <span class="text-foreground-muted">sem agente</span> @endif</td>
                    <td data-label="Versão">{{ $project->deployed_version ?? '—' }}</td>
                    <td data-label="Último backup">{{ $agent?->last_backup_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td data-label="SSL" @class(['text-warning' => $probe?->ssl_expires_at && $probe->ssl_expires_at->lt($today->copy()->addDays(21))])>{{ $probe?->ssl_expires_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-foreground-muted">Nenhum projeto monitorado.</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Incidentes técnicos">
            @forelse ($incidents as $incident)
                <p class="text-sm">{{ $incident->opened_at->format('d/m H:i') }} · {{ $incident->project->name }} — {{ $incident->summary }} @if ($incident->resolved_at)<x-admin.badge tone="success">resolvido {{ $incident->resolved_at->format('d/m H:i') }}</x-admin.badge>@else<x-admin.badge tone="danger">aberto</x-admin.badge>@endif</p>
            @empty
                <p class="text-sm text-foreground-muted">Nenhum incidente.</p>
            @endforelse
        </x-admin.card>
        <x-admin.card title="Domínios e certificados">
            @forelse ($domains as $domain)
                <p class="text-sm">{{ $domain->name }} — domínio: {{ $domain->expires_at?->format('d/m/Y') ?? '—' }}{{ $domain->expires_checked_at ? ' (RDAP '.$domain->expires_checked_at->format('d/m').')' : '' }} · SSL: {{ $domain->ssl_expires_at?->format('d/m/Y') ?? '—' }}</p>
            @empty
                <p class="text-sm text-foreground-muted">Nenhum domínio cadastrado.</p>
            @endforelse
        </x-admin.card>
    </div>
</x-layouts.admin>
