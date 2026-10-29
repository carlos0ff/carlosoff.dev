@php($editing = $client->exists)
<x-layouts.admin :title="$editing ? 'Editar cliente' : 'Novo cliente'" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Clientes' => route('admin.gestao.clients.index'), ($editing ? $client->legal_name : 'Novo') => null]">
    <x-admin.page-header :title="$editing ? 'Editar cliente' : 'Novo cliente'" />

    <form method="POST" action="{{ $editing ? route('admin.gestao.clients.update', $client) : route('admin.gestao.clients.store') }}" novalidate class="space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card title="Identificação">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.select name="person_type" label="Tipo" :options="['pj' => 'Pessoa jurídica', 'pf' => 'Pessoa física']" :value="$client->person_type" required />
                <x-admin.input name="document" label="CPF ou CNPJ" :value="\App\Support\BrazilianDocument::format($client->document)" help="Validado pelos dígitos verificadores." />
                <x-admin.input name="legal_name" label="Nome ou razão social" :value="$client->legal_name" required />
                <x-admin.input name="trade_name" label="Nome fantasia" :value="$client->trade_name" />
                <x-admin.select name="status" label="Status" :options="\App\Enums\ClientStatus::options()" :value="$client->status?->value" required />
                <x-admin.input name="relationship_started_at" label="Início do relacionamento" type="date" :value="$client->relationship_started_at?->format('Y-m-d')" />
            </div>
        </x-admin.card>

        <x-admin.card title="Contato e endereço">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="email" label="E-mail principal" type="email" :value="$client->email" required />
                <x-admin.input name="phone" label="Telefone" :value="$client->phone" />
                <x-admin.input name="whatsapp" label="WhatsApp" :value="$client->whatsapp" />
                <x-admin.input name="address_zip" label="CEP" :value="$client->address_zip" />
                <x-admin.input name="address_street" label="Logradouro" :value="$client->address_street" />
                <x-admin.input name="address_number" label="Número" :value="$client->address_number" />
                <x-admin.input name="address_complement" label="Complemento" :value="$client->address_complement" />
                <x-admin.input name="address_district" label="Bairro" :value="$client->address_district" />
                <x-admin.input name="address_city" label="Cidade" :value="$client->address_city" />
                <x-admin.input name="address_state" label="UF" :value="$client->address_state" maxlength="2" />
            </div>
        </x-admin.card>

        <x-admin.card title="Cobrança">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="billing_email" label="E-mail de cobrança" type="email" :value="$client->billing_email" help="Avisos financeiros vão para este e-mail (ou para o principal)." />
                <x-admin.textarea name="billing_notes" label="Dados de cobrança" :value="$client->billing_notes" rows="3" help="Ex.: centro de custo, exigência de nota fiscal, pedido de compra." />
            </div>
        </x-admin.card>

        <x-admin.card title="Uso interno">
            <x-admin.textarea name="internal_notes" label="Observações internas" :value="$client->internal_notes" rows="4" help="Nunca exibidas no portal do cliente." />
        </x-admin.card>

        <div class="flex gap-2">
            <x-admin.button type="submit" variant="primary">Salvar</x-admin.button>
            <x-admin.button :href="$editing ? route('admin.gestao.clients.show', $client) : route('admin.gestao.clients.index')" variant="ghost">Cancelar</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
