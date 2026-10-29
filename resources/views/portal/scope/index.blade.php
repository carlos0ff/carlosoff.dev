@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.portal title="Solicitações">
    <x-admin.page-header title="Solicitações e orçamentos adicionais" subtitle="Nada fora do escopo é executado ou cobrado sem a sua aprovação." />
    <div class="space-y-4">
        @forelse ($requests as $scope)
            <x-admin.card>
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="font-semibold">#{{ $scope->id }} {{ $scope->title }} <span class="text-sm font-normal text-foreground-muted">— {{ $scope->project->name }}</span></p>
                    <x-admin.status :value="$scope->status" />
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-foreground-secondary">{{ $scope->description }}</p>
                @if ($scope->status->value === 'quoted')
                    <div class="mt-4 rounded-lg border border-warning-border p-4 text-sm" data-quote>
                        <p><strong>Valor adicional:</strong> {{ $money($scope->amount_cents) }} · <strong>Horas:</strong> {{ $scope->estimated_hours }} · <strong>Prazo:</strong> {{ $scope->estimated_days }} dia(s)</p>
                        @if ($scope->impact_analysis)<p class="mt-2 whitespace-pre-line"><strong>Impacto:</strong> {{ $scope->impact_analysis }}</p>@endif
                        @if ($scope->dependencies)<p class="mt-1 whitespace-pre-line"><strong>Dependências:</strong> {{ $scope->dependencies }}</p>@endif
                        <p class="mt-1 whitespace-pre-line"><strong>Critérios de aceite:</strong> {{ $scope->acceptance_criteria }}</p>
                        <form method="POST" action="{{ route('portal.scope.decide', $scope) }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="terms_hash" value="{{ hash('sha256', $scope->termsForConsent()) }}">
                            <x-admin.textarea name="notes" label="Observações" rows="2" />
                            <x-admin.toggle name="consent" label="Concordo com o valor, o prazo e os critérios de aceite acima" />
                            <div class="flex gap-2">
                                <x-admin.button type="submit" name="decision" value="approve" size="sm" variant="primary">Aprovar orçamento</x-admin.button>
                                <x-admin.button type="submit" name="decision" value="reject" size="sm" variant="ghost">Recusar</x-admin.button>
                            </div>
                        </form>
                    </div>
                @elseif ($scope->decided_at)
                    <p class="mt-2 text-xs text-foreground-muted">Decisão registrada em {{ $scope->decided_at->format('d/m/Y H:i') }}.</p>
                @endif
            </x-admin.card>
        @empty
            <x-admin.card><p class="text-sm text-foreground-muted">Nenhuma solicitação. Para pedir uma alteração, abra a página do projeto.</p></x-admin.card>
        @endforelse
    </div>
</x-layouts.portal>
