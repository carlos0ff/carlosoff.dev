<x-layouts.admin title="Clientes" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Clientes' => null]">
    <x-admin.page-header title="Clientes" :subtitle="$counts['all'].' '.($counts['all'] === 1 ? 'cliente' : 'clientes').' · '.($counts['active'] ?? 0).' ativos'">
        <x-admin.button variant="primary" :href="route('admin.gestao.clients.create')">+ Novo cliente</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.gestao.clients.index')" :search="$search" :current="$current" :counts="$counts" placeholder="Nome, e-mail ou CPF/CNPJ..."
        :tabs="['all' => 'Todos'] + \App\Enums\ClientStatus::options()" />

    @if ($clients->isEmpty())
        <x-admin.card :padding="false">
            <x-admin.empty-state :title="$search !== '' || $current !== 'all' ? 'Nenhum cliente encontrado' : 'Nenhum cliente cadastrado'" :description="$search !== '' || $current !== 'all' ? 'Ajuste a busca ou o filtro.' : 'Cadastre o primeiro cliente para vincular projetos, contratos e cobranças.'">
                @if ($search === '' && $current === 'all')<x-admin.button variant="primary" :href="route('admin.gestao.clients.create')">+ Novo cliente</x-admin.button>@endif
            </x-admin.empty-state>
        </x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Cliente</th>
                    <th scope="col" class="admin-col-wide">CPF/CNPJ</th>
                    <th scope="col" class="admin-col-wide">Contato</th>
                    <th scope="col" class="text-right">Projetos</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr data-client-row>
                        <td data-label="Cliente">
                            <span class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-md border border-border bg-card-hover text-xs font-bold text-foreground-secondary" aria-hidden="true">{{ mb_strtoupper(mb_substr($client->displayName(), 0, 2)) }}</span>
                                <span class="min-w-0"><a href="{{ route('admin.gestao.clients.show', $client) }}" class="block truncate font-semibold transition-colors hover:text-primary-hover">{{ $client->displayName() }}</a>@if ($client->trade_name)<span class="block truncate text-xs text-foreground-muted">{{ $client->legal_name }}</span>@endif</span>
                            </span>
                        </td>
                        <td data-label="CPF/CNPJ" class="admin-col-wide whitespace-nowrap text-foreground-secondary">{{ \App\Support\BrazilianDocument::format($client->document) ?: '—' }}</td>
                        <td data-label="Contato" class="admin-col-wide text-foreground-secondary"><span class="block truncate">{{ $client->email }}</span><span class="text-xs text-foreground-muted">{{ $client->phone }}</span></td>
                        <td data-label="Projetos" class="text-right tabular-nums">{{ $client->projects_count }}</td>
                        <td data-label="Status"><x-admin.badge :tone="$client->status->tone()">● {{ $client->status->label() }}</x-admin.badge></td>
                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-admin.button size="sm" :href="route('admin.gestao.clients.show', $client)">Abrir</x-admin.button>
                                <x-admin.dropdown>
                                    <x-admin.menu-item :href="route('admin.gestao.clients.edit', $client)">Editar cadastro</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.create', ['client' => $client->id])">+ Novo projeto</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.clients.show', ['client' => $client, 'aba' => 'financeiro'])">Financeiro</x-admin.menu-item>
                                </x-admin.dropdown>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$clients" />
    @endif
</x-layouts.admin>
