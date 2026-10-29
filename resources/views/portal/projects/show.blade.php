@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.portal :title="$project->name">
    <x-admin.page-header :title="$project->name" :subtitle="$project->description" />

    @if ($project->operational_status->value === 'suspended')
        <div class="mb-6 rounded-xl border border-error-border bg-error-muted p-4 text-sm text-primary-hover">
            O serviço está restrito conforme o contrato ({{ $project->active_restriction?->label() }}). Seus dados e backups estão preservados.
            Para regularizar, consulte <a class="underline" href="{{ route('portal.charges.index') }}">suas cobranças</a>. Se discordar, registre uma contestação ou abra um chamado.
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-border bg-card p-4"><p class="text-xs uppercase text-foreground-muted">Desenvolvimento</p><div class="mt-2"><x-admin.status :value="$project->development_status" /></div></div>
        <div class="rounded-xl border border-border bg-card p-4"><p class="text-xs uppercase text-foreground-muted">Serviço</p><div class="mt-2"><x-admin.status :value="$project->operational_status" /></div></div>
        <div class="rounded-xl border border-border bg-card p-4"><p class="text-xs uppercase text-foreground-muted">Financeiro</p><div class="mt-2"><x-admin.status :value="$project->financial_status" /></div></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Validação de versões" id="validacoes">
                @forelse ($project->validations as $validation)
                    <div class="mb-4 border-b border-border-subtle pb-4 text-sm last:mb-0 last:border-0 last:pb-0">
                        <p class="flex flex-wrap items-center justify-between gap-2"><strong>Versão {{ $validation->version }}</strong> <x-admin.status :value="$validation->outcome" /></p>
                        <p class="mt-1 text-foreground-secondary">Ambiente: {{ $validation->environment === 'staging' ? 'Homologação' : 'Produção' }} @if ($validation->environment_url)— <a class="underline" target="_blank" rel="noopener" href="{{ $validation->environment_url }}">{{ $validation->environment_url }}</a>@endif</p>
                        <p class="mt-2 whitespace-pre-line"><span class="font-semibold">Funcionalidades:</span> {{ $validation->features_evaluated }}</p>
                        @if ($validation->tests_performed)<p class="mt-1 whitespace-pre-line"><span class="font-semibold">Testes realizados:</span> {{ $validation->tests_performed }}</p>@endif
                        @if ($validation->known_issues)<p class="mt-1 whitespace-pre-line"><span class="font-semibold">Pendências conhecidas:</span> {{ $validation->known_issues }}</p>@endif
                        @if ($validation->outcome->value === 'pending')
                            <form method="POST" action="{{ route('portal.validations.respond', $validation) }}" class="mt-3 space-y-3">
                                @csrf
                                <x-admin.select name="outcome" label="Sua avaliação" :options="['approved' => 'Aprovar', 'approved_with_reservations' => 'Aprovar com ressalvas', 'rejected' => 'Reprovar']" />
                                <x-admin.textarea name="client_notes" label="Observações / pendências" rows="3" help="Obrigatório para ressalvas ou reprovação." />
                                <x-admin.toggle name="confirm" label="Avaliei esta versão no ambiente indicado" />
                                <x-admin.button type="submit" size="sm" variant="primary">Registrar avaliação</x-admin.button>
                            </form>
                        @else
                            <p class="mt-2 text-xs text-foreground-muted">Respondido em {{ $validation->responded_at?->format('d/m/Y H:i') }}{{ $validation->client_notes ? ': '.$validation->client_notes : '' }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-foreground-muted">Nenhuma versão aguardando validação.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Documentos">
                @forelse ($documents as $document)
                    <a href="{{ route('portal.documents.show', $document) }}" class="flex justify-between gap-2 py-1 text-sm hover:text-primary-hover">{{ $document->title }} <x-admin.status :value="$document->status" /></a>
                @empty
                    <p class="text-sm text-foreground-muted">Nenhum documento.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Cobranças">
                @forelse ($charges as $charge)
                    <p class="flex flex-wrap justify-between gap-2 py-1 text-sm"><span>{{ $charge->due_date->format('d/m/Y') }} — {{ $charge->description }}</span><span class="flex items-center gap-2 tabular-nums">{{ $money($charge->amount_cents) }} <x-admin.status :value="$charge->status" /></span></p>
                @empty
                    <p class="text-sm text-foreground-muted">Nenhuma cobrança.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Histórico">
                <ul class="space-y-1 text-sm">
                    @forelse ($history as $log)
                        <li><span class="text-foreground-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span> · {{ $log->action->label() }} {{ mb_strtolower($log->resource_type) }}: {{ $log->resource_label }} @if ($log->details)<span class="text-foreground-muted">({{ $log->details }})</span>@endif</li>
                    @empty
                        <li class="text-foreground-muted">Sem eventos.</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Serviços contratados">
                @if ($contract = $project->activeContract)
                    <x-admin.kv class="!grid-cols-1" :items="[
                        'Contrato' => e($contract->number),
                        'Mensalidade' => $money($contract->monthly_fee_cents),
                        'Vencimento' => $contract->billing_day ? 'dia '.$contract->billing_day : null,
                        'Vigência' => ($contract->starts_at?->format('d/m/Y') ?? '—').' a '.($contract->ends_at?->format('d/m/Y') ?? 'indeterminado'),
                        'Suporte' => nl2br(e($contract->clause('support_included'))),
                        'Manutenção' => nl2br(e($contract->clause('maintenance_included'))),
                    ]" />
                @else
                    <p class="text-sm text-foreground-muted">Sem contrato vigente.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Domínio e hospedagem">
                @foreach ($project->domains as $domain)
                    <p class="text-sm"><strong>{{ $domain->name }}</strong><br>Titular: {{ $domain->registrant_name ?? 'não informado' }} · expira {{ $domain->expires_at?->format('d/m/Y') ?? '—' }}<br>SSL até {{ $domain->ssl_expires_at?->format('d/m/Y') ?? '—' }}</p>
                @endforeach
                @foreach ($project->hostingServices as $hosting)
                    <p class="mt-2 text-sm"><strong>{{ $hosting->provider }}</strong> {{ $hosting->plan }}<br>Backup: {{ $hosting->backup_policy ?? 'não informado' }}</p>
                @endforeach
                @if ($project->domains->isEmpty() && $project->hostingServices->isEmpty())<p class="text-sm text-foreground-muted">Nada cadastrado.</p>@endif
            </x-admin.card>

            <x-admin.card title="Abrir chamado">
                <form method="POST" action="{{ route('portal.tickets.store', $project) }}" class="space-y-3">
                    @csrf
                    <x-admin.input name="subject" label="Assunto" required />
                    <x-admin.select name="priority" label="Prioridade" :options="\App\Enums\TicketPriority::options()" value="normal" />
                    <x-admin.textarea name="description" label="Descrição" required rows="3" />
                    <x-admin.button type="submit" size="sm">Abrir chamado</x-admin.button>
                </form>
            </x-admin.card>

            <x-admin.card title="Solicitar alteração">
                @if ($project->blocksNewRequests())
                    <p class="text-sm text-warning">Novas solicitações estão temporariamente indisponíveis conforme o contrato.</p>
                @else
                    <form method="POST" action="{{ route('portal.scope.store', $project) }}" class="space-y-3">
                        @csrf
                        <x-admin.input name="title" label="O que você precisa?" required />
                        <x-admin.textarea name="description" label="Detalhes" required rows="3" />
                        <x-admin.textarea name="reason" label="Motivo" rows="2" />
                        <x-admin.button type="submit" size="sm">Enviar solicitação</x-admin.button>
                    </form>
                    <p class="mt-2 text-xs text-foreground-muted">Você recebe a análise e o orçamento antes de qualquer execução ou cobrança.</p>
                @endif
            </x-admin.card>
        </div>
    </div>
</x-layouts.portal>
