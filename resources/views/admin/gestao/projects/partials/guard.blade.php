<div class="mb-4 border-b border-border-subtle pb-4 last:mb-0 last:border-0 last:pb-0" data-pending-command>
    <p class="text-sm"><x-admin.status :value="$command->type" /> {{ $command->reason }} <span class="text-foreground-muted">— {{ $command->triggered_by_label }}, {{ $command->created_at->format('d/m/Y H:i') }}</span></p>
    @if ($command->restriction_mode)<p class="mt-1 text-sm">Forma prevista em contrato: <strong>{{ $command->restriction_mode->label() }}</strong></p>@endif
    @if ($command->guard_report)
        <ul class="mt-2 space-y-1 text-xs">
            @foreach ($command->guard_report['checks'] as $check)
                <li class="{{ $check['passed'] ? 'text-success' : 'text-error' }}">{{ $check['passed'] ? '✓' : '✕' }} {{ $check['label'] }} — <span class="text-foreground-secondary">{{ $check['detail'] }}</span></li>
            @endforeach
        </ul>
        <p class="mt-1 text-xs text-foreground-muted">As verificações são refeitas no momento da aprovação.</p>
    @endif
    <div class="mt-3 flex flex-wrap gap-2">
        @can('gestao.admin')
            <form method="POST" action="{{ route('admin.gestao.commands.approve', $command->uuid) }}" data-confirm="O comando será liberado para execução. Dados e backups são preservados; a ação é reversível." data-confirm-title="Aprovar {{ mb_strtolower($command->type->label()) }}?" data-confirm-button="Aprovar">@csrf<x-admin.button type="submit" size="sm" variant="primary">Aprovar</x-admin.button></form>
        @endcan
        <form method="POST" action="{{ route('admin.gestao.commands.cancel', $command->uuid) }}" class="flex gap-2">@csrf<input name="reason" required placeholder="Motivo da recusa" class="rounded-md border border-border bg-background px-3 py-1.5 text-sm"><x-admin.button type="submit" size="sm" variant="ghost">Recusar</x-admin.button></form>
    </div>
</div>
