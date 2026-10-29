@if ($pendingApproval->isNotEmpty())
    <x-admin.card title="Aguardando aprovação" class="mb-6 !border-warning-border" id="operacional">
        @foreach ($pendingApproval as $command)
            @include('admin.gestao.projects.partials.guard', ['command' => $command])
        @endforeach
    </x-admin.card>
@endif

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Comandos remotos" :padding="false" id="comandos">
            @if ($commands->isEmpty())
                <x-admin.empty-state title="Nenhum comando enviado" description="Manutenções, restrições contratuais e reativações aparecem aqui com origem, aprovação e confirmação do agente." />
            @else
                <ul class="divide-y divide-border-subtle">
                    @foreach ($commands as $command)
                        <li class="px-5 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="flex flex-wrap items-center gap-2"><x-admin.status :value="$command->type" /><x-admin.status :value="$command->status" /><span class="text-xs text-foreground-muted">canal {{ $command->channel }}</span></span>
                                <time class="text-xs text-foreground-muted">{{ $command->created_at->format('d/m/Y H:i') }}</time>
                            </div>
                            <p class="mt-1">{{ $command->reason }}</p>
                            <p class="text-xs text-foreground-muted">{{ $command->triggered_by_label }}@if ($command->approver) · aprovado por {{ $command->approver->name }}@endif @if ($command->restriction_mode) · {{ $command->restriction_mode->label() }}@endif @if ($command->acknowledged_at) · confirmado {{ $command->acknowledged_at->format('d/m H:i') }}@endif</p>
                            @if ($command->channel === 'operator' && $command->status->value === 'queued')
                                <form method="POST" action="{{ route('admin.gestao.commands.confirm-operator', $command->uuid) }}" class="mt-2 flex flex-wrap gap-2">@csrf<input name="notes" required placeholder="Evidência da execução manual" class="min-w-0 flex-1 rounded-md border border-border bg-background px-3 py-1.5 text-sm"><x-admin.button type="submit" size="sm">Registrar execução</x-admin.button></form>
                            @endif
                            @if ($command->status->value === 'queued')
                                <form method="POST" action="{{ route('admin.gestao.commands.cancel', $command->uuid) }}" class="mt-2 flex flex-wrap gap-2">@csrf<input name="reason" required placeholder="Motivo do cancelamento" class="min-w-0 flex-1 rounded-md border border-border bg-background px-3 py-1.5 text-sm"><x-admin.button type="submit" size="sm" variant="ghost">Cancelar</x-admin.button></form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>

        <x-admin.card title="Monitoramento" :padding="false">
            <x-slot:actions>
                @can('gestao.operations')<form method="POST" action="{{ route('admin.gestao.projects.probe', $project) }}">@csrf<x-admin.button type="submit" size="sm">Verificar agora</x-admin.button></form>@endcan
            </x-slot:actions>
            @if ($checks->isEmpty())
                <x-admin.empty-state title="Sem verificações ainda" description="A verificação externa roda a cada 5 minutos para projetos com URL de produção." />
            @else
                <x-admin.table class="!rounded-none !border-0">
                    <thead><tr><th scope="col">Quando</th><th scope="col">Origem</th><th scope="col">Resultado</th><th scope="col" class="admin-col-wide">Detalhes</th></tr></thead>
                    <tbody>
                        @foreach ($checks as $item)
                            <tr>
                                <td data-label="Quando" class="whitespace-nowrap text-foreground-secondary">{{ $item->checked_at->format('d/m H:i') }}</td>
                                <td data-label="Origem">{{ $item->source === 'agent' ? 'Agente' : 'Externa' }}</td>
                                <td data-label="Resultado"><x-admin.badge :tone="$item->is_up ? 'success' : 'danger'">● {{ $item->is_up ? 'OK' : 'Falha' }}</x-admin.badge></td>
                                <td data-label="Detalhes" class="admin-col-wide text-xs text-foreground-secondary">{{ collect([$item->http_status ? 'HTTP '.$item->http_status : null, $item->response_time_ms ? $item->response_time_ms.' ms' : null, $item->deployed_version ? 'v'.$item->deployed_version : null, $item->last_backup_at ? 'backup '.$item->last_backup_at->format('d/m H:i') : null, $item->error])->filter()->implode(' · ') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-admin.table>
            @endif
        </x-admin.card>

        @if ($incidents->isNotEmpty())
            <x-admin.card title="Incidentes técnicos" :padding="false">
                <ul class="divide-y divide-border-subtle">
                    @foreach ($incidents as $incident)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm"><span>{{ $incident->opened_at->format('d/m H:i') }} — {{ $incident->summary }}</span>@if ($incident->resolved_at)<x-admin.badge tone="success">resolvido</x-admin.badge>@else<x-admin.badge tone="danger">aberto</x-admin.badge>@endif</li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif
    </div>

    <div class="space-y-6">
        <x-admin.card title="Enviar comando">
            @can('gestao.operations')
                <form method="POST" action="{{ route('admin.gestao.projects.commands.store', $project) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <x-admin.select name="type" label="Comando" :options="['maintenance' => 'Manutenção programada', 'reactivate' => 'Reativar serviço']" />
                    <x-admin.input name="reason" label="Motivo" required />
                    <x-admin.textarea name="message" label="Mensagem aos usuários" :rows="2" help="Exibida na página de manutenção. Nunca inclua dados financeiros." />
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                        <x-admin.input name="starts_at" label="Início" type="datetime-local" />
                        <x-admin.input name="ends_at" label="Fim" type="datetime-local" help="Horário de Brasília." />
                    </div>
                    <x-admin.button type="submit" size="sm" variant="primary">Enviar comando</x-admin.button>
                </form>
                <p class="mt-4 text-xs text-foreground-muted">Restrição por inadimplência só é proposta a partir de uma cobrança vencida e passa pelas verificações do contrato.</p>
            @else
                <p class="text-sm text-foreground-muted">Sem permissão para enviar comandos.</p>
            @endcan
        </x-admin.card>

        <x-admin.card title="Credenciais da API" :padding="false">
            @if ($credentials->isEmpty())
                <p class="px-5 py-4 text-sm text-foreground-muted">Nenhuma credencial. Emita uma para instalar o agente no servidor do projeto.</p>
            @else
                <ul class="divide-y divide-border-subtle">
                    @foreach ($credentials as $credential)
                        <li class="px-5 py-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="min-w-0"><span class="block truncate font-medium">{{ $credential->name }}</span><code class="text-xs text-foreground-muted">{{ $credential->key_id }}</code></span>
                                @if ($credential->revoked_at)<x-admin.badge tone="danger">revogada</x-admin.badge>@elseif (! $credential->isUsable())<x-admin.badge>expirada</x-admin.badge>@elseif ($credential->expires_at)<x-admin.badge tone="warning">expira {{ $credential->expires_at->diffForHumans() }}</x-admin.badge>@else<x-admin.badge tone="success">● ativa</x-admin.badge>@endif
                            </div>
                            <p class="mt-1 text-xs text-foreground-muted">Último uso: {{ $credential->last_used_at?->format('d/m/Y H:i') ?? 'nunca' }}</p>
                            @if ($credential->isUsable())
                                @can('gestao.operations')
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.gestao.credentials.rotate', $credential) }}">@csrf<x-admin.button type="submit" size="sm">Rotacionar</x-admin.button></form>
                                        <form method="POST" action="{{ route('admin.gestao.credentials.revoke', $credential) }}" class="flex gap-1" data-confirm="O agente deixará de se comunicar imediatamente." data-confirm-title="Revogar credencial?" data-confirm-button="Revogar">@csrf<input name="reason" required placeholder="Motivo" class="w-24 rounded-md border border-border bg-background px-2 text-xs"><x-admin.button type="submit" size="sm" variant="danger">Revogar</x-admin.button></form>
                                    </div>
                                @endcan
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @can('gestao.operations')
                <form method="POST" action="{{ route('admin.gestao.projects.credentials.store', $project) }}" class="flex gap-2 border-t border-border-subtle px-5 py-4">
                    @csrf
                    <input name="name" required placeholder="Ex.: servidor produção" aria-label="Nome da instalação" class="min-w-0 flex-1 rounded-md border border-border bg-background px-3 py-1.5 text-sm outline-none focus:border-primary">
                    <x-admin.button type="submit" size="sm">Emitir</x-admin.button>
                </form>
            @endcan
        </x-admin.card>
    </div>
</div>
