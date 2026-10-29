<x-layouts.admin title="Nova cobrança" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Cobranças' => route('admin.gestao.charges.index'), 'Nova' => null]">
    <x-admin.page-header title="Nova cobrança avulsa" subtitle="Mensalidades de contratos vigentes são geradas automaticamente (gestao:generate-charges)." />

    <form method="POST" action="{{ route('admin.gestao.charges.store') }}" novalidate>
        @csrf
        <x-admin.card>
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.select name="client_project_id" label="Projeto" :options="['' => 'Selecione...'] + $projects->all()" :value="$project?->id" required class="md:col-span-2" />
                <x-admin.select name="kind" label="Tipo" :options="\App\Enums\ChargeKind::options()" />
                <x-admin.input name="description" label="Descrição" required />
                <x-admin.input name="amount" label="Valor (R$)" required help="Ex.: 1.500,00" />
                <x-admin.input name="due_date" label="Vencimento" type="date" required />
            </div>
            <div class="mt-5"><x-admin.button type="submit" variant="primary">Emitir cobrança</x-admin.button></div>
        </x-admin.card>
    </form>
</x-layouts.admin>
