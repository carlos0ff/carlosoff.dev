@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.admin title="Contratos" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Contratos' => null]">
    <x-admin.page-header title="Contratos" :subtitle="$counts['all'].' '.($counts['all'] === 1 ? 'contrato' : 'contratos').' · '.($counts['active'] ?? 0).' vigentes'">
        <x-admin.button :href="route('admin.gestao.templates.index')">Modelos</x-admin.button>
        <x-admin.button variant="primary" :href="route('admin.gestao.projects.index')">+ Novo contrato</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.gestao.contracts.index')" :search="$search" :current="$current" :counts="$counts" placeholder="Número ou objeto..."
        :tabs="['all' => 'Todos'] + collect(\App\Enums\ContractStatus::options())->except('none')->all()" />

    @if ($contracts->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhum contrato encontrado" description="Contratos são criados a partir da página do projeto (menu ⋮ → Novo contrato)." /></x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Contrato</th>
                    <th scope="col">Cliente / projeto</th>
                    <th scope="col" class="text-right">Mensalidade</th>
                    <th scope="col" class="admin-col-wide">Vigência</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contracts as $contract)
                    <tr>
                        <td data-label="Contrato"><a class="font-semibold transition-colors hover:text-primary-hover" href="{{ route('admin.gestao.contracts.show', $contract) }}">{{ $contract->number }}</a><span class="block max-w-[16rem] truncate text-xs text-foreground-muted">{{ $contract->object }}</span></td>
                        <td data-label="Cliente / projeto">{{ $contract->client->displayName() }}<span class="block text-xs text-foreground-muted">{{ $contract->project->name }}</span></td>
                        <td data-label="Mensalidade" class="text-right tabular-nums">{{ $contract->monthly_fee_cents ? $money($contract->monthly_fee_cents) : '—' }}</td>
                        <td data-label="Vigência" class="admin-col-wide whitespace-nowrap text-foreground-secondary">{{ $contract->starts_at?->format('d/m/Y') ?? '—' }} → {{ $contract->ends_at?->format('d/m/Y') ?? 'indeterminado' }}</td>
                        <td data-label="Status"><x-admin.badge :tone="$contract->status->tone()">● {{ $contract->status->label() }}</x-admin.badge></td>
                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-admin.button size="sm" :href="route('admin.gestao.contracts.show', $contract)">Abrir</x-admin.button>
                                <x-admin.dropdown>
                                    @if (in_array($contract->status->value, ['draft', 'awaiting_signature'], true))<x-admin.menu-item :href="route('admin.gestao.contracts.edit', $contract)">Editar minuta</x-admin.menu-item>@endif
                                    <x-admin.menu-item :href="route('admin.gestao.contracts.show', $contract).'#politica'">Política financeira</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', ['project' => $contract->project, 'aba' => 'documentos'])">Gerar documento</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', $contract->project)">Abrir projeto</x-admin.menu-item>
                                </x-admin.dropdown>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$contracts" />
    @endif
</x-layouts.admin>
