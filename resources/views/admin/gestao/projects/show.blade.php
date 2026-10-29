@php
    $money = fn ($cents) => \App\Support\Money::format($cents);
    $overdueCount = $openOverdue->count();
    $tabs = [
        'visao' => 'Visão geral',
        'documentos' => ['Contratos e documentos', $documents->whereIn('status.value', ['awaiting_signature', 'partially_signed'])->count() ?: null],
        'financeiro' => ['Financeiro', $overdueCount ?: null],
        'operacao' => ['Operação e API', $pendingApproval->count() ?: null],
        'historico' => 'Histórico',
    ];
    $tab = array_key_exists(request('aba'), $tabs) ? request('aba') : 'visao';
@endphp

<x-layouts.admin :title="$project->name" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Projetos' => route('admin.gestao.projects.index'), $project->name => null]">
    <x-admin.page-header :title="$project->name" :subtitle="$project->client->displayName().($project->technicalLead ? ' · responsável: '.$project->technicalLead->name : '')">
        <x-admin.button :href="route('admin.gestao.projects.edit', $project)">Editar</x-admin.button>
        <x-admin.dropdown label="Mais ações">
            <x-admin.menu-item :href="route('admin.gestao.contracts.create', $project)">+ Novo contrato</x-admin.menu-item>
            @can('gestao.finance')<x-admin.menu-item :href="route('admin.gestao.charges.create', ['project' => $project->id])">+ Nova cobrança</x-admin.menu-item>@endcan
            <x-admin.menu-item :href="route('admin.gestao.clients.show', $project->client)">Abrir cliente</x-admin.menu-item>
            @if ($project->production_url)<x-admin.menu-item :href="$project->production_url" target="_blank" rel="noopener">Abrir produção ↗</x-admin.menu-item>@endif
        </x-admin.dropdown>
    </x-admin.page-header>

    {{-- Estados independentes --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4" data-project-states>
        @foreach (['Desenvolvimento' => $project->development_status, 'Contrato' => $project->contract_status, 'Financeiro' => $project->financial_status, 'Operação' => $project->operational_status] as $label => $state)
            <div class="rounded-xl border border-border bg-card px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-foreground-muted">{{ $label }}</p>
                <p class="mt-1.5"><x-admin.badge :tone="$state->tone()">● {{ $state->label() }}</x-admin.badge></p>
            </div>
        @endforeach
    </div>

    @if (session('issued_secret'))
        @php($issued = session('issued_secret'))
        <div role="alert" class="mb-6 rounded-xl border border-warning-border bg-warning-muted p-4">
            <p class="text-sm font-semibold text-warning">Credencial emitida — copie agora, o segredo não será exibido novamente</p>
            <p class="mt-1 text-xs text-foreground-secondary">Configure no servidor do projeto (variáveis de ambiente, nunca no frontend).</p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-background p-3 text-xs" data-issued-secret>CENTRAL_API_URL={{ url('/api/v1') }}
CENTRAL_PROJECT_ID={{ $issued['project'] }}
CENTRAL_KEY_ID={{ $issued['key_id'] }}
CENTRAL_SECRET={{ $issued['secret'] }}</pre>
        </div>
    @endif

    @if ($pendingApproval->isNotEmpty() && $tab !== 'operacao')
        <a href="{{ request()->fullUrlWithQuery(['aba' => 'operacao']) }}" class="mb-6 flex items-center justify-between gap-3 rounded-xl border border-warning-border bg-warning-muted px-4 py-3 text-sm text-warning transition-colors hover:border-warning">
            <span><strong>{{ $pendingApproval->count() }}</strong> {{ $pendingApproval->count() === 1 ? 'comando aguarda' : 'comandos aguardam' }} aprovação</span>
            <span class="font-semibold">Revisar →</span>
        </a>
    @endif

    <x-admin.tabs :tabs="$tabs" :current="$tab" label="Seções do projeto" />

    @include('admin.gestao.projects.tabs.'.$tab)
</x-layouts.admin>
