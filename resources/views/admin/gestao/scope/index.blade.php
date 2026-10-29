<x-layouts.admin title="Solicitações adicionais" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Solicitações' => null]">
    <x-admin.page-header title="Solicitações fora do escopo" :subtitle="($counts['quoted'] ?? 0).' aguardando o cliente · nada é executado ou cobrado sem aprovação registrada'" />
    <x-admin.filters :action="route('admin.gestao.scope.index')" :current="$current" :counts="$counts" placeholder="—" :tabs="['all' => 'Todas'] + \App\Enums\ScopeRequestStatus::options()" />
    @if ($requests->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhuma solicitação" description="Clientes pedem alterações pelo portal; a equipe também pode registrar na página do projeto." /></x-admin.card>
    @else
        <x-admin.table>
            <thead><tr><th scope="col">Solicitação</th><th scope="col" class="admin-col-wide">Projeto</th><th scope="col" class="text-right">Valor</th><th scope="col" class="admin-col-wide text-right">Prazo</th><th scope="col">Status</th><th scope="col" class="text-right">Ações</th></tr></thead>
            <tbody>
                @foreach ($requests as $scope)
                    <tr>
                        <td data-label="Solicitação"><a class="font-semibold transition-colors hover:text-primary-hover" href="{{ route('admin.gestao.scope.show', $scope) }}">#{{ $scope->id }} {{ $scope->title }}</a><span class="block text-xs text-foreground-muted">aberta em {{ $scope->created_at->format('d/m/Y') }}</span></td>
                        <td data-label="Projeto" class="admin-col-wide">{{ $scope->project->name }}<span class="block text-xs text-foreground-muted">{{ $scope->project->client->displayName() }}</span></td>
                        <td data-label="Valor" class="text-right tabular-nums">{{ $scope->amount_cents !== null ? \App\Support\Money::format($scope->amount_cents) : '—' }}</td>
                        <td data-label="Prazo" class="admin-col-wide text-right tabular-nums text-foreground-secondary">{{ $scope->estimated_days ? $scope->estimated_days.' dia(s)' : '—' }}</td>
                        <td data-label="Status"><x-admin.badge :tone="$scope->status->tone()">● {{ $scope->status->label() }}</x-admin.badge></td>
                        <td data-label=""><div class="flex justify-end"><x-admin.button size="sm" :href="route('admin.gestao.scope.show', $scope)">{{ in_array($scope->status->value, ['requested', 'under_analysis'], true) ? 'Orçar' : 'Abrir' }}</x-admin.button></div></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$requests" />
    @endif
</x-layouts.admin>
