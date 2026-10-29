@php($editing = $contract->exists)
<x-layouts.admin :title="$editing ? 'Editar contrato' : 'Novo contrato'" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Projetos' => route('admin.gestao.projects.index'), $project->name => route('admin.gestao.projects.show', $project), ($editing ? $contract->number : 'Novo contrato') => null]">
    <x-admin.page-header :title="$editing ? 'Editar contrato '.$contract->number : 'Novo contrato'" :subtitle="$project->name.' — '.$project->client->displayName()" />

    <div class="mb-6 rounded-xl border border-warning-border bg-warning-muted p-4 text-sm text-warning">
        O sistema não valida juridicamente as cláusulas. Revise o texto com profissional habilitado antes do uso comercial.
    </div>

    <form method="POST" action="{{ $editing ? route('admin.gestao.contracts.update', $contract) : route('admin.gestao.contracts.store', $project) }}" novalidate class="space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card title="Objeto e escopo">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="object" label="Objeto do contrato" :value="$contract->object" required class="md:col-span-2" />
                <x-admin.textarea name="scope" label="Escopo contratado" :value="$contract->scope" rows="5" />
                <x-admin.textarea name="deliverables" label="Entregáveis" :value="$contract->deliverables" rows="5" />
            </div>
        </x-admin.card>

        <x-admin.card title="Valores, pagamento e vigência">
            <div class="grid gap-5 md:grid-cols-3">
                <x-admin.input name="setup_fee" label="Implantação (R$)" :value="\App\Support\Money::toInput($contract->setup_fee_cents)" />
                <x-admin.input name="monthly_fee" label="Mensalidade (R$)" :value="\App\Support\Money::toInput($contract->monthly_fee_cents)" help="Gera cobranças recorrentes enquanto o contrato estiver vigente." />
                <x-admin.input name="billing_day" label="Dia de vencimento" type="number" min="1" max="28" :value="$contract->billing_day" />
                <x-admin.input name="payment_method" label="Forma de pagamento" :value="$contract->payment_method" help="Ex.: Pix, boleto" />
                <x-admin.input name="starts_at" label="Início da vigência" type="date" :value="$contract->starts_at?->format('Y-m-d')" />
                <x-admin.input name="ends_at" label="Fim da vigência" type="date" :value="$contract->ends_at?->format('Y-m-d')" help="Vazio = prazo indeterminado" />
                <x-admin.textarea name="payment_terms" label="Condições de pagamento" :value="$contract->payment_terms" rows="3" class="md:col-span-3" />
                <x-admin.textarea name="renewal_rules" label="Regras de renovação" :value="$contract->renewal_rules" rows="3" class="md:col-span-3" />
            </div>
        </x-admin.card>

        <x-admin.card title="Cláusulas">
            <div class="grid gap-5 md:grid-cols-2">
                @foreach (\App\Models\Contract::CLAUSES as $key => $label)
                    <x-admin.textarea name="clauses[{{ $key }}]" :label="$label" :value="$contract->clause($key)" rows="4" />
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card title="Suporte: prazos por prioridade">
            <p class="mb-4 text-xs text-foreground-muted">Prazo de resposta (primeiro retorno) é diferente de prazo de solução. Só informe prazos que a operação consegue cumprir.</p>
            <div class="grid gap-4 md:grid-cols-4">
                @foreach (\App\Enums\TicketPriority::cases() as $priority)
                    <div class="space-y-2 rounded-lg border border-border-subtle p-3">
                        <p class="text-sm font-semibold">{{ $priority->label() }}</p>
                        <x-admin.input name="sla[{{ $priority->value }}][response_hours]" label="Resposta (h)" type="number" :value="$contract->support_sla[$priority->value]['response_hours'] ?? null" />
                        <x-admin.input name="sla[{{ $priority->value }}][resolution_hours]" label="Solução estimada (h)" type="number" :value="$contract->support_sla[$priority->value]['resolution_hours'] ?? null" />
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        @if ($editing)
            <x-admin.card title="Versão">
                <x-admin.input name="change_summary" label="Resumo da alteração" required help="Fica registrado no histórico de versões." />
            </x-admin.card>
        @endif

        <div class="flex gap-2">
            <x-admin.button type="submit" variant="primary">Salvar</x-admin.button>
            <x-admin.button :href="$editing ? route('admin.gestao.contracts.show', $contract) : route('admin.gestao.projects.show', $project)" variant="ghost">Cancelar</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
