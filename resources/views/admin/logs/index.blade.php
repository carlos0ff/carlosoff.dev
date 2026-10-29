<x-layouts.admin title="Logs" :breadcrumbs="['Sistema' => null, 'Logs' => null]">
    <x-admin.page-header title="Logs de auditoria" subtitle="Histórico das alterações feitas no painel." />

    <form method="GET" action="{{ route('admin.logs.index') }}" class="mb-4 flex flex-wrap gap-2" role="search">
        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Usuário ou item..." class="min-w-0 flex-1 rounded-md border border-border bg-card px-3 py-2 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary sm:max-w-xs">

        <select name="action" aria-label="Ação" class="rounded-md border border-border bg-card px-3 py-2 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary">
            <option value="">Todas as ações</option>
            @foreach (\App\Enums\AuditAction::cases() as $action)
                <option value="{{ $action->value }}" @selected($filters['action'] === $action->value)>{{ $action->value }}</option>
            @endforeach
        </select>

        <select name="resource" aria-label="Recurso" class="rounded-md border border-border bg-card px-3 py-2 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary">
            <option value="">Todos os recursos</option>
            @foreach ($resources as $resource)
                <option value="{{ $resource }}" @selected($filters['resource'] === $resource)>{{ $resource }}</option>
            @endforeach
        </select>

        <x-admin.button type="submit">Filtrar</x-admin.button>
        @if ($filters['search'] !== '' || $filters['action'] || $filters['resource'] !== '')
            <x-admin.button :href="route('admin.logs.index')" variant="ghost">Limpar</x-admin.button>
        @endif
    </form>

    @if ($logs->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhum registro encontrado" description="As alterações feitas no painel aparecem aqui." /></x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Usuário</th>
                    <th scope="col">Ação</th>
                    <th scope="col">Recurso</th>
                    <th scope="col">Item</th>
                    <th scope="col">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr data-log-row>
                        <td data-label="Usuário" class="font-semibold">{{ $log->user_name }}</td>
                        <td data-label="Ação"><x-admin.badge :tone="match ($log->action->value) { 'DELETE', 'UNPUBLISH' => 'warning', 'CREATE', 'PUBLISH', 'FEATURE' => 'success', default => 'muted' }">{{ $log->action->value }}</x-admin.badge></td>
                        <td data-label="Recurso">{{ $log->resource_type }}</td>
                        <td data-label="Item">{{ $log->resource_label ?? '—' }}@if ($log->details) <span class="text-xs text-foreground-muted">({{ $log->details }})</span>@endif</td>
                        <td data-label="Data" class="whitespace-nowrap text-foreground-secondary">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>

        <x-admin.pagination :paginator="$logs" />
    @endif
</x-layouts.admin>
