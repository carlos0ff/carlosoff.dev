<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Contratos" :padding="false">
            <x-slot:actions><x-admin.button size="sm" :href="route('admin.gestao.contracts.create', $project)">+ Novo contrato</x-admin.button></x-slot:actions>
            @forelse ($project->contracts as $contract)
                <a href="{{ route('admin.gestao.contracts.show', $contract) }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0 transition-colors hover:bg-card-hover">
                    <span class="min-w-0"><strong>{{ $contract->number }}</strong> <span class="text-foreground-secondary">— {{ $contract->object }}</span><span class="block text-xs text-foreground-muted">v{{ $contract->version }} · {{ $contract->starts_at?->format('d/m/Y') ?? '—' }} → {{ $contract->ends_at?->format('d/m/Y') ?? 'indeterminado' }}</span></span>
                    <span class="flex items-center gap-2 tabular-nums">{{ $money($contract->monthly_fee_cents) }}/mês <x-admin.badge :tone="$contract->status->tone()">● {{ $contract->status->label() }}</x-admin.badge></span>
                </a>
            @empty
                <x-admin.empty-state title="Nenhum contrato" description="Crie o contrato: as cláusulas padrão já vêm preenchidas e podem ser ajustadas.">
                    <x-admin.button variant="primary" :href="route('admin.gestao.contracts.create', $project)">+ Novo contrato</x-admin.button>
                </x-admin.empty-state>
            @endforelse
        </x-admin.card>

        <x-admin.card title="Documentos" :padding="false">
            @if ($documents->isEmpty())
                <x-admin.empty-state title="Nenhum documento gerado" description="Use o painel ao lado para gerar contratos, termos e aceites a partir dos modelos." />
            @else
                <ul class="divide-y divide-border-subtle">
                    @foreach ($documents as $document)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                            <a href="{{ route('admin.gestao.documents.show', $document) }}" class="min-w-0 transition-colors hover:text-primary-hover"><span class="font-medium">{{ $document->type->label() }}</span> <span class="text-foreground-muted">v{{ $document->version }}</span><span class="block text-xs text-foreground-muted">{{ $document->updated_at->format('d/m/Y H:i') }}</span></a>
                            <x-admin.badge :tone="$document->status->tone()">● {{ $document->status->label() }}</x-admin.badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>

        <x-admin.card title="Validações de versão" :padding="false">
            @forelse ($project->validations->sortByDesc('id') as $validation)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0">
                    <span class="min-w-0"><strong>v{{ $validation->version }}</strong> <span class="text-foreground-secondary">({{ $validation->environment === 'staging' ? 'homologação' : 'produção' }}) · enviada em {{ $validation->created_at->format('d/m/Y') }}</span>
                        @if ($validation->client_notes)<span class="block text-xs text-foreground-muted">“{{ \Illuminate\Support\Str::limit($validation->client_notes, 100) }}”</span>@endif</span>
                    <x-admin.badge :tone="$validation->outcome->tone()">● {{ $validation->outcome->label() }}</x-admin.badge>
                </div>
            @empty
                <x-admin.empty-state title="Nenhuma validação solicitada" />
            @endforelse
            <details class="border-t border-border-subtle px-5 py-4">
                <summary class="cursor-pointer text-sm font-semibold text-primary-hover">+ Solicitar validação ao cliente</summary>
                <form method="POST" action="{{ route('admin.gestao.projects.validations.store', $project) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <x-admin.input name="version" label="Versão entregue" required />
                    <x-admin.select name="environment" label="Ambiente" :options="['staging' => 'Homologação', 'production' => 'Produção']" />
                    <x-admin.input name="environment_url" label="URL do ambiente" type="url" :value="$project->staging_url" class="md:col-span-2" />
                    <x-admin.textarea name="features_evaluated" label="Funcionalidades a avaliar" required class="md:col-span-2" />
                    <x-admin.textarea name="tests_performed" label="Testes realizados" />
                    <x-admin.textarea name="known_issues" label="Pendências conhecidas" />
                    <x-admin.textarea name="evidence" label="Evidências (links, anexos)" class="md:col-span-2" />
                    <p class="text-xs text-foreground-muted md:col-span-2">A ausência de resposta não é considerada aceite.</p>
                    <div><x-admin.button type="submit" variant="primary" size="sm">Enviar ao cliente</x-admin.button></div>
                </form>
            </details>
        </x-admin.card>

        <x-admin.card title="Solicitações fora do escopo" :padding="false">
            @forelse ($project->scopeRequests->sortByDesc('id') as $scope)
                <a href="{{ route('admin.gestao.scope.show', $scope) }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0 transition-colors hover:bg-card-hover">
                    <span>#{{ $scope->id }} {{ $scope->title }}</span>
                    <span class="flex items-center gap-2">@if ($scope->amount_cents !== null)<span class="tabular-nums text-foreground-secondary">{{ $money($scope->amount_cents) }}</span>@endif<x-admin.badge :tone="$scope->status->tone()">● {{ $scope->status->label() }}</x-admin.badge></span>
                </a>
            @empty
                <x-admin.empty-state title="Nenhuma solicitação adicional" />
            @endforelse
            <details class="border-t border-border-subtle px-5 py-4">
                <summary class="cursor-pointer text-sm font-semibold text-primary-hover">+ Registrar solicitação</summary>
                <form method="POST" action="{{ route('admin.gestao.scope.store', $project) }}" class="mt-4 space-y-4">
                    @csrf
                    <x-admin.input name="title" label="Título" required />
                    <x-admin.textarea name="description" label="Descrição" required />
                    <x-admin.textarea name="reason" label="Motivo" :rows="2" />
                    <x-admin.button type="submit" size="sm">Registrar</x-admin.button>
                </form>
            </details>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Gerar documento">
            <form method="POST" action="{{ route('admin.gestao.documents.store', $project) }}" class="space-y-4">
                @csrf
                <x-admin.select name="type" label="Tipo" :options="$documentTypes" />
                <x-admin.select name="contract_id" label="Contrato" :options="['' => 'Mais recente'] + $project->contracts->pluck('number', 'id')->all()" />
                <x-admin.select name="scope_request_id" label="Solicitação (termo de escopo)" :options="['' => '—'] + $project->scopeRequests->mapWithKeys(fn ($s) => [$s->id => '#'.$s->id.' '.$s->title])->all()" />
                <x-admin.select name="project_validation_id" label="Validação (termo de aceite)" :options="['' => '—'] + $project->validations->mapWithKeys(fn ($v) => [$v->id => 'v'.$v->version.' — '.$v->outcome->label()])->all()" />
                <x-admin.button type="submit" variant="primary" size="sm">Gerar rascunho</x-admin.button>
            </form>
            <p class="mt-4 text-xs text-foreground-muted">O texto vem do modelo com os dados do cadastro. Só modelos com revisão jurídica registrada podem ir para assinatura.</p>
        </x-admin.card>

        <x-admin.card title="Fluxo de assinatura">
            <ol class="space-y-2 text-sm text-foreground-secondary">
                <li><span class="font-semibold text-foreground">1.</span> Gerar rascunho e revisar o texto</li>
                <li><span class="font-semibold text-foreground">2.</span> Conferir signatários</li>
                <li><span class="font-semibold text-foreground">3.</span> Congelar versão (PDF + SHA-256)</li>
                <li><span class="font-semibold text-foreground">4.</span> Enviar ao provedor de assinatura</li>
                <li><span class="font-semibold text-foreground">5.</span> Acompanhar eventos e baixar o PDF assinado</li>
            </ol>
        </x-admin.card>
    </div>
</div>
