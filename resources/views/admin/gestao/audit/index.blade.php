<x-layouts.admin title="Auditoria" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Auditoria' => null]">
    <x-admin.page-header title="Histórico de auditoria" subtitle="Registro append-only de contratos, cobranças, pagamentos, comandos, assinaturas, aceites e acessos." />

    @php($select = 'rounded-md border border-border bg-card px-3 py-2 text-sm outline-none hover:border-border-hover focus:border-primary')
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Item, detalhe ou autor..." class="{{ $select }} min-w-0 flex-1 sm:max-w-xs">
        <select name="action" class="{{ $select }}"><option value="">Todas as ações</option>@foreach (\App\Enums\AuditAction::cases() as $a)<option value="{{ $a->value }}" @selected(($filters['action'] ?? '') === $a->value)>{{ $a->value }}</option>@endforeach</select>
        <select name="client" class="{{ $select }}"><option value="">Todos os clientes</option>@foreach ($clients as $id => $name)<option value="{{ $id }}" @selected(($filters['client'] ?? '') == $id)>{{ $name }}</option>@endforeach</select>
        <select name="project" class="{{ $select }}"><option value="">Todos os projetos</option>@foreach ($projects as $id => $name)<option value="{{ $id }}" @selected(($filters['project'] ?? '') == $id)>{{ $name }}</option>@endforeach</select>
        <select name="actor" class="{{ $select }}"><option value="">Qualquer origem</option>@foreach (['user' => 'Usuário', 'api' => 'API', 'rule' => 'Regra', 'system' => 'Sistema', 'webhook' => 'Webhook', 'signer' => 'Signatário'] as $v => $l)<option value="{{ $v }}" @selected(($filters['actor'] ?? '') === $v)>{{ $l }}</option>@endforeach</select>
        <x-admin.button type="submit">Filtrar</x-admin.button>
    </form>

    <x-admin.table>
        <thead><tr><th>Data</th><th>Origem</th><th>Ação</th><th>Recurso</th><th>Detalhes</th><th>IP</th></tr></thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td data-label="Data" class="whitespace-nowrap text-foreground-secondary">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td data-label="Origem">{{ $log->user_name }} <span class="text-xs text-foreground-muted">({{ $log->actor_type }})</span></td>
                    <td data-label="Ação"><x-admin.badge>{{ $log->action->value }}</x-admin.badge></td>
                    <td data-label="Recurso">{{ $log->resource_type }}: <strong>{{ $log->resource_label }}</strong></td>
                    <td data-label="Detalhes" class="text-foreground-secondary">{{ $log->details }}</td>
                    <td data-label="IP" class="text-xs text-foreground-muted">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-foreground-muted">Nenhum registro.</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <x-admin.pagination :paginator="$logs" />
</x-layouts.admin>
