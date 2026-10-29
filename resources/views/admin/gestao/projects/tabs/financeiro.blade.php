@php($open = $charges->filter(fn ($c) => $c->isOpen()))
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-admin.stat label="Mensalidade contratada" :value="$money($project->activeContract?->monthly_fee_cents ?? 0)" :hint="$project->activeContract ? 'contrato '.$project->activeContract->number : 'sem contrato vigente'" />
    <x-admin.stat label="Em aberto" :value="$money($open->sum(fn ($c) => $c->outstandingCents()))" :hint="$open->count().' '.($open->count() === 1 ? 'cobrança' : 'cobranças')" />
    <x-admin.stat label="Vencido" :value="$money($openOverdue->sum(fn ($c) => $c->outstandingCents()))" :hint="$overdueCount ? $overdueCount.' vencida(s)' : 'nada vencido'" />
    <x-admin.stat label="Recebido (últimas)" :value="$money($charges->sum('paid_cents'))" hint="nas cobranças listadas abaixo" />
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-admin.card title="Cobranças" :padding="false">
            <x-slot:actions>
                @can('gestao.finance')<x-admin.button size="sm" :href="route('admin.gestao.charges.create', ['project' => $project->id])">+ Nova cobrança</x-admin.button>@endcan
            </x-slot:actions>
            @if ($charges->isEmpty())
                <x-admin.empty-state title="Nenhuma cobrança" description="Mensalidades do contrato vigente são geradas automaticamente." />
            @else
                <ul class="divide-y divide-border-subtle">
                    @foreach ($charges as $charge)
                        <li>
                            <a href="{{ route('admin.gestao.charges.show', $charge) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm transition-colors hover:bg-card-hover">
                                <span class="min-w-0"><span class="font-medium">{{ $charge->description }}</span><span class="block text-xs text-foreground-muted">vence {{ $charge->due_date->format('d/m/Y') }}{{ $charge->status->value === 'overdue' ? ' · '.$charge->daysOverdue().' dia(s) de atraso' : '' }}</span></span>
                                <span class="flex items-center gap-2"><span class="tabular-nums">{{ $money($charge->amount_cents) }}</span><x-admin.badge :tone="$charge->status->tone()">● {{ $charge->status->label() }}</x-admin.badge></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>

    <div class="space-y-6">
        @if ($policy = $project->activeContract?->policy)
            <x-admin.card title="Política do contrato">
                <x-admin.kv class="!grid-cols-1" :items="[
                    'Tolerância' => $policy->grace_days.' dia(s) após o vencimento',
                    'Aviso prévio' => $policy->pre_suspension_notice_days.' dia(s)',
                    'Restrição prevista' => $policy->suspension_allowed ? e($policy->restriction_mode->label()) : '<span class=\'text-foreground-muted\'>não prevista em contrato</span>',
                    'Aprovação humana' => $policy->suspension_requires_approval ? 'Exigida' : 'Não exigida',
                    'Reativação' => e($policy->reactivation_mode->label()),
                ]" />
                <a href="{{ route('admin.gestao.contracts.show', $project->activeContract) }}#politica" class="mt-3 inline-block text-xs font-semibold text-primary-hover hover:underline">Editar política →</a>
            </x-admin.card>
        @endif

        @can('gestao.finance')
            <x-admin.card title="Exceção de suspensão">
                @if ($project->hasSuspensionHold())
                    <p class="mb-4 rounded-lg border border-info-border bg-info-muted px-3 py-2 text-sm text-info">Vigente até {{ $project->suspension_hold_until->format('d/m/Y') }}: {{ $project->suspension_hold_reason }}</p>
                @else
                    <p class="mb-4 text-sm text-foreground-secondary">Enquanto houver exceção vigente, nenhuma restrição é proposta para este projeto.</p>
                @endif
                <form method="POST" action="{{ route('admin.gestao.projects.hold', $project) }}" class="space-y-4">
                    @csrf
                    <x-admin.input name="suspension_hold_until" label="Sem suspensão até" type="date" :value="$project->suspension_hold_until?->format('Y-m-d')" />
                    <x-admin.input name="suspension_hold_reason" label="Motivo autorizado" :value="$project->suspension_hold_reason" />
                    <x-admin.button type="submit" size="sm">Salvar exceção</x-admin.button>
                </form>
            </x-admin.card>
        @endcan
    </div>
</div>
