<x-layouts.admin :title="$scope->title" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Solicitações' => route('admin.gestao.scope.index'), '#'.$scope->id => null]">
    <x-admin.page-header :title="'#'.$scope->id.' '.$scope->title" :subtitle="$scope->project->name">
        <x-admin.status :value="$scope->status" />
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Solicitação">
                <x-admin.kv :items="[
                    'Aberta por' => e($scope->requester?->name),
                    'Em' => $scope->created_at->format('d/m/Y H:i'),
                    'Descrição' => nl2br(e($scope->description)),
                    'Motivo' => nl2br(e($scope->reason)),
                ]" />
            </x-admin.card>

            @if ($scope->decided_at)
                <x-admin.card title="Decisão do cliente (consentimento)">
                    <x-admin.kv :items="[
                        'Decisão' => $scope->status->label(),
                        'Por' => e($scope->decider?->name ?? 'assinatura eletrônica'),
                        'Em' => $scope->decided_at->format('d/m/Y H:i:s'),
                        'IP' => e($scope->decision_ip),
                        'Navegador' => e($scope->decision_user_agent),
                        'Hash dos termos aceitos' => '<code class=\'text-xs break-all\'>'.e($scope->decision_terms_hash).'</code>',
                        'Observações' => nl2br(e($scope->decision_notes)),
                    ]" />
                </x-admin.card>
            @endif

            <x-admin.card title="Termos de solicitação">
                @forelse ($documents as $document)
                    <a href="{{ route('admin.gestao.documents.show', $document) }}" class="flex justify-between py-1 text-sm hover:text-primary-hover">{{ $document->title }} v{{ $document->version }} <x-admin.status :value="$document->status" /></a>
                @empty
                    <p class="text-sm text-foreground-muted">Gere o "Termo de solicitação fora do escopo" na página do projeto para coletar assinatura.</p>
                @endforelse
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Análise e orçamento">
                @if (in_array($scope->status->value, ['requested', 'under_analysis', 'quoted'], true))
                    <form method="POST" action="{{ route('admin.gestao.scope.quote', $scope) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <x-admin.textarea name="affected_features" label="Funcionalidades afetadas" :value="$scope->affected_features" rows="2" />
                        <x-admin.textarea name="impact_analysis" label="Análise de impacto" :value="$scope->impact_analysis" rows="3" required />
                        <x-admin.input name="estimated_hours" label="Horas estimadas" type="number" step="0.5" :value="$scope->estimated_hours" required />
                        <x-admin.input name="amount" label="Valor adicional (R$)" :value="\App\Support\Money::toInput($scope->amount_cents)" required />
                        <x-admin.input name="estimated_days" label="Prazo (dias)" type="number" :value="$scope->estimated_days" required />
                        <x-admin.textarea name="dependencies" label="Dependências" :value="$scope->dependencies" rows="2" />
                        <x-admin.textarea name="acceptance_criteria" label="Critérios de aceite" :value="$scope->acceptance_criteria" rows="3" required />
                        <x-admin.button type="submit" size="sm" variant="primary">{{ $scope->status->value === 'quoted' ? 'Atualizar orçamento' : 'Enviar orçamento ao cliente' }}</x-admin.button>
                    </form>
                @else
                    <x-admin.kv class="!grid-cols-1" :items="[
                        'Valor' => \App\Support\Money::format($scope->amount_cents),
                        'Horas' => e($scope->estimated_hours),
                        'Prazo' => $scope->estimated_days.' dia(s)',
                        'Critérios de aceite' => nl2br(e($scope->acceptance_criteria)),
                    ]" />
                @endif
            </x-admin.card>

            <x-admin.card title="Situação">
                <form method="POST" action="{{ route('admin.gestao.scope.status', $scope) }}" class="flex gap-2">
                    @csrf
                    <select name="status" class="rounded-md border border-border bg-background px-3 py-1.5 text-sm"><option value="delivered">Marcar entregue</option><option value="cancelled">Cancelar</option></select>
                    <x-admin.button type="submit" size="sm">Aplicar</x-admin.button>
                </form>
                <p class="mt-2 text-xs text-foreground-muted">"Aprovada" só é definido pelo cliente no portal ou pela assinatura do termo.</p>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
