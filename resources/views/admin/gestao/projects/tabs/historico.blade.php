<x-admin.card title="Histórico de alterações" :padding="false">
    <x-slot:actions><a href="{{ route('admin.gestao.audit.index', ['project' => $project->id]) }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Auditoria completa</a></x-slot:actions>
    @if ($history->isEmpty())
        <x-admin.empty-state title="Sem registros ainda" />
    @else
        <ul class="divide-y divide-border-subtle">
            @foreach ($history as $log)
                <li class="flex flex-wrap items-start justify-between gap-2 px-5 py-3 text-sm">
                    <span class="min-w-0">
                        <strong>{{ $log->user_name }}</strong> {{ mb_strtolower($log->action->label()) }} <span class="text-foreground-secondary">{{ mb_strtolower($log->resource_type) }}</span> <strong>{{ $log->resource_label }}</strong>
                        @if ($log->details)<span class="block text-xs text-foreground-muted">{{ $log->details }}</span>@endif
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        @if ($log->visible_to_client)<x-admin.badge tone="info">visível ao cliente</x-admin.badge>@endif
                        <time class="text-xs text-foreground-muted" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-admin.card>
