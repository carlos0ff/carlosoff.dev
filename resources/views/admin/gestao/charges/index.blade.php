@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.admin title="Cobranças" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Cobranças' => null]">
    <x-admin.page-header title="Cobranças e pagamentos" :subtitle="($counts['overdue'] ?? 0).' vencidas · '.(($counts['pending'] ?? 0) + ($counts['partially_paid'] ?? 0)).' em aberto · pagamento só conta como recebido após confirmação'">
        @can('gestao.finance')<x-admin.button variant="primary" :href="route('admin.gestao.charges.create')">+ Nova cobrança</x-admin.button>@endcan
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.gestao.charges.index')" :search="$search" :current="$current" :counts="$counts" placeholder="Descrição..."
        :tabs="['all' => 'Todas'] + \App\Enums\ChargeStatus::options()" />

    @if ($charges->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhuma cobrança encontrada" description="Mensalidades de contratos vigentes são geradas automaticamente; cobranças avulsas podem ser emitidas aqui." /></x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Vencimento</th>
                    <th scope="col">Cobrança</th>
                    <th scope="col" class="admin-col-wide">Cliente</th>
                    <th scope="col" class="text-right">Valor</th>
                    <th scope="col" class="admin-col-wide text-right">Recebido</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($charges as $charge)
                    <tr>
                        <td data-label="Vencimento" class="whitespace-nowrap">
                            {{ $charge->due_date->format('d/m/Y') }}
                            @if ($charge->status->value === 'overdue')<span class="block text-xs text-error">{{ $charge->daysOverdue() }} dia(s) de atraso</span>@endif
                        </td>
                        <td data-label="Cobrança">
                            <a class="font-semibold transition-colors hover:text-primary-hover" href="{{ route('admin.gestao.charges.show', $charge) }}">{{ $charge->description }}</a>
                            <span class="block text-xs text-foreground-muted">{{ $charge->kind->label() }} · {{ $charge->project->name }}</span>
                            @if ($charge->isDisputed())<x-admin.badge tone="info">contestada</x-admin.badge>@endif
                            @if ($charge->isUnderNegotiation())<x-admin.badge tone="info">em negociação</x-admin.badge>@endif
                        </td>
                        <td data-label="Cliente" class="admin-col-wide text-foreground-secondary">{{ $charge->client->displayName() }}</td>
                        <td data-label="Valor" class="text-right tabular-nums font-semibold">{{ $money($charge->amount_cents) }}</td>
                        <td data-label="Recebido" class="admin-col-wide text-right tabular-nums text-foreground-secondary">{{ $charge->paid_cents ? $money($charge->paid_cents) : '—' }}</td>
                        <td data-label="Status"><x-admin.badge :tone="$charge->status->tone()">● {{ $charge->status->label() }}</x-admin.badge></td>
                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-admin.button size="sm" :href="route('admin.gestao.charges.show', $charge)">{{ $charge->isOpen() ? 'Dar baixa' : 'Abrir' }}</x-admin.button>
                                <x-admin.dropdown>
                                    <x-admin.menu-item :href="route('admin.gestao.charges.show', $charge)">Detalhes e histórico</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', ['project' => $charge->project, 'aba' => 'financeiro'])">Financeiro do projeto</x-admin.menu-item>
                                    <x-admin.menu-item :href="route('admin.gestao.clients.show', $charge->client_id)">Abrir cliente</x-admin.menu-item>
                                </x-admin.dropdown>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$charges" />
    @endif
</x-layouts.admin>
