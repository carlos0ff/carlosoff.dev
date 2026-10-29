@php
    $money = fn ($cents) => \App\Support\Money::format($cents);
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $period = \Illuminate\Support\Carbon::createFromFormat('Y-m', $filters['period']);
    $periods = collect(range(0, 5))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m') => ucfirst(now()->startOfMonth()->subMonthsNoOverflow($i)->translatedFormat('M/y'))])->all();
    $select = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary';
    $pending = [
        ['label' => 'Aguardando assinatura', 'value' => $kpis['awaiting_signature'], 'href' => route('admin.gestao.documents.index', ['status' => 'awaiting_signature']), 'tone' => 'warning'],
        ['label' => 'Validações com o cliente', 'value' => $kpis['awaiting_validation'], 'href' => route('admin.gestao.projects.index', ['development' => 'awaiting_approval']), 'tone' => 'warning'],
        ['label' => 'Adicionais aguardando aprovação', 'value' => $kpis['scope_awaiting'], 'href' => route('admin.gestao.scope.index', ['status' => 'quoted']), 'tone' => 'warning'],
        ['label' => 'Cobranças pendentes', 'value' => $kpis['charges_pending'], 'href' => route('admin.gestao.charges.index', ['status' => 'pending']), 'tone' => 'info'],
        ['label' => 'Cobranças vencidas', 'value' => $kpis['charges_overdue'], 'href' => route('admin.gestao.charges.index', ['status' => 'overdue']), 'tone' => 'danger'],
        ['label' => 'Em manutenção ou suspensão', 'value' => $kpis['maintenance_or_suspended'], 'href' => route('admin.gestao.projects.index', ['operational' => 'suspended']), 'tone' => 'danger'],
        ['label' => 'Incidentes técnicos abertos', 'value' => $openIncidents->count(), 'href' => route('admin.gestao.monitoring.index'), 'tone' => 'info'],
    ];
@endphp

<x-layouts.admin title="Gestão de clientes" :breadcrumbs="['Gestão' => null]">
    <x-admin.page-header title="Gestão de clientes" :subtitle="$kpis['clients'].' '.($kpis['clients'] === 1 ? 'cliente ativo' : 'clientes ativos').' · '.$kpis['in_development'].' em desenvolvimento · '.$kpis['in_production'].' em produção'">
        <x-admin.button :href="route('admin.gestao.clients.create')">+ Novo cliente</x-admin.button>
        <x-admin.button variant="primary" :href="route('admin.gestao.projects.create')">+ Novo projeto</x-admin.button>
    </x-admin.page-header>

    {{-- Período + filtros --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <x-admin.range-tabs :options="$periods" :current="$filters['period']" param="period" label="Mês de referência" />

        <details class="group w-full sm:w-auto" @if ($activeFilters) open @endif>
            <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-border bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground transition-colors hover:border-border-hover hover:bg-secondary-hover [&::-webkit-details-marker]:hidden">
                Filtros @if ($activeFilters)<span class="rounded-full bg-primary px-1.5 text-[10px] text-primary-foreground">{{ $activeFilters }}</span>@endif
            </summary>
            <form method="GET" class="mt-2 grid gap-2 rounded-xl border border-border bg-card p-3 sm:w-[36rem] sm:grid-cols-2" data-dashboard-filters>
                <input type="hidden" name="period" value="{{ $filters['period'] }}">
                <select name="client" aria-label="Cliente" class="{{ $select }}"><option value="">Todos os clientes</option>@foreach ($clients as $id => $name)<option value="{{ $id }}" @selected($filters['client'] == $id)>{{ $name }}</option>@endforeach</select>
                <select name="project" aria-label="Projeto" class="{{ $select }}"><option value="">Todos os projetos</option>@foreach ($projectOptions as $id => $name)<option value="{{ $id }}" @selected($filters['project'] == $id)>{{ $name }}</option>@endforeach</select>
                <select name="status" aria-label="Status" class="{{ $select }}">
                    <option value="">Qualquer status</option>
                    <optgroup label="Desenvolvimento">@foreach (\App\Enums\DevelopmentStatus::options() as $v => $l)<option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>@endforeach</optgroup>
                    <optgroup label="Financeiro">@foreach (\App\Enums\FinancialStatus::options() as $v => $l)<option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>@endforeach</optgroup>
                    <optgroup label="Operacional">@foreach (\App\Enums\OperationalStatus::options() as $v => $l)<option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>@endforeach</optgroup>
                </select>
                <select name="lead" aria-label="Responsável" class="{{ $select }}"><option value="">Qualquer responsável</option>@foreach ($leads as $id => $name)<option value="{{ $id }}" @selected($filters['lead'] == $id)>{{ $name }}</option>@endforeach</select>
                <div class="flex gap-2 sm:col-span-2"><x-admin.button type="submit" size="sm" variant="primary">Aplicar</x-admin.button><x-admin.button size="sm" variant="ghost" :href="route('admin.gestao.dashboard', ['period' => $filters['period']])">Limpar</x-admin.button></div>
            </form>
        </details>
    </div>

    @if ($pendingCommands->isNotEmpty())
        <div role="alert" class="mb-6 rounded-xl border border-warning-border bg-warning-muted p-4">
            <p class="text-sm font-semibold text-warning">{{ $pendingCommands->count() }} {{ $pendingCommands->count() === 1 ? 'ação aguarda' : 'ações aguardam' }} aprovação</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                @foreach ($pendingCommands as $command)
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span><x-admin.status :value="$command->type" /> <strong>{{ $command->project->name }}</strong> <span class="text-foreground-secondary">— {{ $command->reason }}</span></span>
                        <a href="{{ route('admin.gestao.projects.show', ['project' => $command->project, 'aba' => 'operacao']) }}" class="text-xs font-semibold text-primary-hover hover:underline">Revisar →</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Financeiro --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Receita recorrente" :value="$money($finance['mrr_contracted'])" hint="contratada (contratos vigentes)" :href="route('admin.gestao.contracts.index', ['status' => 'active'])" />
        <x-admin.stat label="Recebido no mês" :value="$money($finance['received'])" hint="pagamentos confirmados" />
        <x-admin.stat label="Faturado no mês" :value="$money($finance['invoiced'])" :hint="'previsto a receber: '.$money($finance['forecast'])" />
        <x-admin.stat label="Vencido em aberto" :value="$money($finance['overdue'])" :hint="$kpis['charges_overdue'].' '.($kpis['charges_overdue'] === 1 ? 'cobrança vencida' : 'cobranças vencidas')" :href="route('admin.gestao.charges.index', ['status' => 'overdue'])" :class="$finance['overdue'] > 0 ? '!border-error-border' : ''" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Recebido × faturado" class="lg:col-span-2">
            <x-slot:actions><span class="flex items-center gap-3 text-xs text-foreground-muted"><span class="flex items-center gap-1"><span class="h-0.5 w-4 rounded bg-primary"></span>Recebido</span><span class="flex items-center gap-1"><span class="h-0 w-4 border-t border-dashed border-foreground-muted"></span>Faturado</span></span></x-slot:actions>
            @if (array_sum($series['received']) + array_sum($series['invoiced']) > 0)
                <x-admin.line-chart :labels="$series['labels']" :values="$series['received']" :secondary="$series['invoiced']" secondary-label="Faturado" prefix="R$ " unit="recebidos" unit-singular="recebido" aria-label="Valores recebidos e faturados nos últimos 12 meses" />
                <p class="mt-2 text-xs text-foreground-muted">Últimos 12 meses, em reais. "Recebido" soma só pagamentos confirmados; cobrança emitida não é dinheiro em caixa.</p>
            @else
                <x-admin.empty-state title="Sem movimento financeiro ainda" description="As cobranças emitidas e os pagamentos confirmados aparecem aqui." />
            @endif
        </x-admin.card>

        <x-admin.card title="Pendências" :padding="false">
            <ul class="divide-y divide-border-subtle">
                @foreach ($pending as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm transition-colors hover:bg-card-hover">
                            <span>{{ $item['label'] }}</span>
                            @if ($item['value'] > 0)
                                <x-admin.badge :tone="$item['tone']">{{ $item['value'] }}</x-admin.badge>
                            @else
                                <span class="tabular-nums text-foreground-muted">0</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Receita recorrente por cliente">
            <x-admin.bar-list :items="$mrrByClient" prefix="R$ " empty="Nenhum contrato vigente com mensalidade." />
        </x-admin.card>
        <x-admin.card title="Em aberto por cliente">
            <x-slot:actions><a href="{{ route('admin.gestao.charges.index') }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Ver cobranças</a></x-slot:actions>
            <x-admin.bar-list :items="$openByClient" prefix="R$ " empty="Nenhuma cobrança em aberto." />
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Vencidas" :padding="false">
            @forelse ($overdueList as $charge)
                <a href="{{ route('admin.gestao.charges.show', $charge) }}" class="flex items-center justify-between gap-3 border-b border-border-subtle px-5 py-3 text-sm last:border-0 transition-colors hover:bg-card-hover">
                    <span class="min-w-0"><span class="block truncate font-medium">{{ $charge->description }}</span><span class="text-xs text-foreground-muted">{{ $charge->project->client->displayName() }} · {{ $charge->project->name }}</span></span>
                    <span class="shrink-0 text-right"><span class="block tabular-nums font-semibold text-error">{{ $money($charge->outstandingCents()) }}</span><span class="text-xs text-foreground-muted">{{ $charge->daysOverdue() }} dia(s)</span></span>
                </a>
            @empty
                <x-admin.empty-state title="Nenhuma cobrança vencida" />
            @endforelse
        </x-admin.card>

        <x-admin.card title="Vencem nos próximos 7 dias" :padding="false">
            @forelse ($dueSoon as $charge)
                <a href="{{ route('admin.gestao.charges.show', $charge) }}" class="flex items-center justify-between gap-3 border-b border-border-subtle px-5 py-3 text-sm last:border-0 transition-colors hover:bg-card-hover">
                    <span class="min-w-0"><span class="block truncate font-medium">{{ $charge->description }}</span><span class="text-xs text-foreground-muted">{{ $charge->project->client->displayName() }}</span></span>
                    <span class="shrink-0 text-right"><span class="block tabular-nums">{{ $money($charge->outstandingCents()) }}</span><span class="text-xs text-foreground-muted">{{ $charge->due_date->format('d/m') }}</span></span>
                </a>
            @empty
                <x-admin.empty-state title="Nada vencendo esta semana" />
            @endforelse
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Domínios, SSL e hospedagem" :padding="false">
            @php($renewals = $expiringDomains->isEmpty() && $renewingHosting->isEmpty())
            @if ($renewals)
                <x-admin.empty-state title="Nenhum vencimento próximo" description="Domínios, certificados e hospedagens que vencem em breve aparecem aqui." />
            @else
                <ul class="divide-y divide-border-subtle text-sm">
                    @foreach ($expiringDomains as $domain)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="min-w-0"><span class="block truncate font-medium">{{ $domain->name }}</span><span class="text-xs text-foreground-muted">{{ $domain->project?->name }}</span></span>
                            <span class="flex shrink-0 flex-col items-end gap-1">
                                @if ($domain->expires_at)<x-admin.badge tone="warning">Domínio {{ $domain->expires_at->format('d/m/Y') }}</x-admin.badge>@endif
                                @if ($domain->ssl_expires_at)<x-admin.badge tone="warning">SSL {{ $domain->ssl_expires_at->format('d/m/Y') }}</x-admin.badge>@endif
                            </span>
                        </li>
                    @endforeach
                    @foreach ($renewingHosting as $hosting)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="min-w-0"><span class="block truncate font-medium">{{ $hosting->provider }} {{ $hosting->plan }}</span><span class="text-xs text-foreground-muted">{{ $hosting->project?->name }}</span></span>
                            <x-admin.badge tone="info">Hospedagem {{ $hosting->renews_at->format('d/m/Y') }}</x-admin.badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>

        <x-admin.card title="Operação" :padding="false">
            @if ($attentionProjects->isEmpty() && $openIncidents->isEmpty())
                <x-admin.empty-state title="Todos os projetos operando normalmente" />
            @else
                <ul class="divide-y divide-border-subtle text-sm">
                    @foreach ($attentionProjects as $project)
                        <li><a href="{{ route('admin.gestao.projects.show', ['project' => $project, 'aba' => 'operacao']) }}" class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-card-hover"><span class="truncate font-medium">{{ $project->name }}</span><x-admin.status :value="$project->operational_status" /></a></li>
                    @endforeach
                    @foreach ($openIncidents as $incident)
                        <li class="flex items-center justify-between gap-3 px-5 py-3"><span class="min-w-0 truncate">{{ $incident->project->name }} — {{ $incident->summary }}</span><x-admin.badge tone="info">Técnico</x-admin.badge></li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>

    <x-admin.card title="Atividade recente" class="mt-6" :padding="false">
        <x-slot:actions><a href="{{ route('admin.gestao.audit.index') }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Ver auditoria</a></x-slot:actions>
        @if ($recentLogs->isEmpty())
            <x-admin.empty-state title="Nenhuma atividade registrada ainda" />
        @else
            <ul class="divide-y divide-border-subtle">
                @foreach ($recentLogs as $log)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                        <span><strong>{{ $log->user_name }}</strong> {{ mb_strtolower($log->action->label()) }} <span class="text-foreground-secondary">{{ mb_strtolower($log->resource_type) }}</span> <strong>{{ $log->resource_label }}</strong>@if ($log->details) <span class="text-foreground-muted">· {{ \Illuminate\Support\Str::limit($log->details, 70) }}</span>@endif</span>
                        <time class="text-xs text-foreground-muted" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</x-layouts.admin>
