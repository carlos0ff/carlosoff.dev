@php($select = 'rounded-md border border-border bg-card px-3 py-2 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary')
<x-layouts.admin title="Projetos de clientes" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Projetos' => null]">
    <x-admin.page-header title="Projetos de clientes" :subtitle="$projects->total().' '.($projects->total() === 1 ? 'projeto' : 'projetos').' · desenvolvimento, contrato, financeiro e operação são estados independentes'">
        <x-admin.button variant="primary" :href="route('admin.gestao.projects.create')">+ Novo projeto</x-admin.button>
    </x-admin.page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-2" role="search">
        <label class="relative w-full shrink-0 sm:w-64">
            <span class="sr-only">Pesquisar</span>
            <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-foreground-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Pesquisar projeto..." class="{{ $select }} w-full pl-9">
        </label>
        <select name="client" aria-label="Cliente" class="{{ $select }} min-w-[9rem] flex-1"><option value="">Todos os clientes</option>@foreach ($clients as $id => $name)<option value="{{ $id }}" @selected(($filters['client'] ?? null) == $id)>{{ $name }}</option>@endforeach</select>
        <select name="development" aria-label="Desenvolvimento" class="{{ $select }} min-w-[9rem] flex-1"><option value="">Desenvolvimento</option>@foreach (\App\Enums\DevelopmentStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['development'] ?? null) === $v)>{{ $l }}</option>@endforeach</select>
        <select name="financial" aria-label="Financeiro" class="{{ $select }} min-w-[9rem] flex-1"><option value="">Financeiro</option>@foreach (\App\Enums\FinancialStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['financial'] ?? null) === $v)>{{ $l }}</option>@endforeach</select>
        <select name="operational" aria-label="Operacional" class="{{ $select }} min-w-[9rem] flex-1"><option value="">Operacional</option>@foreach (\App\Enums\OperationalStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['operational'] ?? null) === $v)>{{ $l }}</option>@endforeach</select>
        <select name="lead" aria-label="Responsável" class="{{ $select }} min-w-[9rem] flex-1"><option value="">Responsável</option>@foreach ($leads as $id => $name)<option value="{{ $id }}" @selected(($filters['lead'] ?? null) == $id)>{{ $name }}</option>@endforeach</select>
        <x-admin.button type="submit">Filtrar</x-admin.button>
        @if ($search !== '' || array_filter($filters))<x-admin.button :href="route('admin.gestao.projects.index')" variant="ghost">Limpar</x-admin.button>@endif
    </form>

    @if ($projects->isEmpty())
        <x-admin.card :padding="false">
            <x-admin.empty-state :title="$search !== '' || array_filter($filters) ? 'Nenhum projeto encontrado' : 'Nenhum projeto cadastrado'" :description="$search !== '' || array_filter($filters) ? 'Ajuste a busca ou os filtros.' : 'Cadastre um projeto vinculado a um cliente.'" />
        </x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Projeto</th>
                    <th scope="col">Desenvolvimento</th>
                    <th scope="col" class="admin-col-wide">Contrato</th>
                    <th scope="col">Financeiro</th>
                    <th scope="col">Operação</th>
                    <th scope="col" class="admin-col-wide">Saúde</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr data-project-row>
                        <td data-label="Projeto">
                            <a href="{{ route('admin.gestao.projects.show', $project) }}" class="font-semibold transition-colors hover:text-primary-hover">{{ $project->name }}</a>
                            <span class="block text-xs text-foreground-muted">{{ $project->client->displayName() }}{{ $project->technicalLead ? ' · '.$project->technicalLead->name : '' }}</span>
                        </td>
                        <td data-label="Desenvolvimento"><x-admin.status :value="$project->development_status" /></td>
                        <td data-label="Contrato" class="admin-col-wide"><x-admin.status :value="$project->contract_status" /></td>
                        <td data-label="Financeiro"><x-admin.status :value="$project->financial_status" /></td>
                        <td data-label="Operação"><x-admin.status :value="$project->operational_status" /></td>
                        <td data-label="Saúde" class="admin-col-wide">
                            @if ($check = $project->latestHealthCheck)
                                <span class="inline-flex items-center gap-1.5 text-xs text-foreground-secondary" title="{{ $check->error }}"><span @class(['h-2 w-2 rounded-full', 'bg-success' => $check->is_up, 'bg-error' => ! $check->is_up])></span>{{ $check->checked_at->diffForHumans(short: true) }}</span>
                            @else
                                <span class="text-xs text-foreground-muted">sem dados</span>
                            @endif
                        </td>
                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-admin.button size="sm" :href="route('admin.gestao.projects.show', $project)">Abrir</x-admin.button>
                                <x-admin.dropdown>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.edit', $project)">Editar projeto</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.contracts.create', $project)">+ Novo contrato</x-admin.menu-item>
                                    @can('gestao.finance')<x-admin.menu-item :href="route('admin.gestao.charges.create', ['project' => $project->id])">+ Nova cobrança</x-admin.menu-item>@endcan
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', ['project' => $project, 'aba' => 'documentos'])">Documentos</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', ['project' => $project, 'aba' => 'operacao'])">Operação e API</x-admin.menu-item>
                                </x-admin.dropdown>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$projects" />
    @endif
</x-layouts.admin>
