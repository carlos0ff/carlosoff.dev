@php($editing = $project->exists)
<x-layouts.admin :title="$editing ? 'Editar projeto' : 'Novo projeto'" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Projetos' => route('admin.gestao.projects.index'), ($editing ? $project->name : 'Novo') => null]">
    <x-admin.page-header :title="$editing ? 'Editar projeto' : 'Novo projeto'" subtitle="Os status financeiro e operacional não são editáveis aqui: vêm das cobranças e dos comandos auditados." />

    <form method="POST" action="{{ $editing ? route('admin.gestao.projects.update', $project) : route('admin.gestao.projects.store') }}" novalidate class="space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-admin.card title="Projeto">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.select name="client_id" label="Cliente" :options="['' => 'Selecione...'] + $clients->all()" :value="$project->client_id" required :disabled="$editing" />
                @if ($editing)<input type="hidden" name="client_id" value="{{ $project->client_id }}">@endif
                <x-admin.input name="name" label="Nome do projeto" :value="$project->name" required />
                <x-admin.select name="technical_lead_id" label="Responsável técnico" :options="['' => '—'] + $leads->all()" :value="$project->technical_lead_id" />
                <x-admin.select name="development_status" label="Status do desenvolvimento" :options="\App\Enums\DevelopmentStatus::options()" :value="$project->development_status?->value" required />
                <x-admin.textarea name="description" label="Descrição" :value="$project->description" rows="3" />
                <x-admin.textarea name="objective" label="Objetivo" :value="$project->objective" rows="3" />
                <x-admin.input name="technologies" label="Tecnologias" :value="$project->technologies" help="Ex.: Laravel 13, MySQL, Vue" />
                <x-admin.input name="repository_url" label="Repositório" type="url" :value="$project->repository_url" />
            </div>
        </x-admin.card>

        <x-admin.card title="Ambientes e datas">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="primary_domain" label="Domínio principal" :value="$project->primary_domain" />
                <x-admin.input name="staging_url" label="Homologação (URL)" type="url" :value="$project->staging_url" />
                <x-admin.input name="production_url" label="Produção (URL)" type="url" :value="$project->production_url" help="Usada pelo monitoramento externo." />
                <x-admin.toggle name="monitoring_enabled" label="Monitorar disponibilidade" :checked="$project->monitoring_enabled ?? true" />
                <x-admin.input name="started_at" label="Início" type="date" :value="$project->started_at?->format('Y-m-d')" />
                <x-admin.input name="due_at" label="Entrega prevista" type="date" :value="$project->due_at?->format('Y-m-d')" />
                <x-admin.input name="published_at" label="Publicação" type="date" :value="$project->published_at?->format('Y-m-d')" />
            </div>
        </x-admin.card>

        <x-admin.card title="Comercial (referência)">
            <p class="mb-4 text-xs text-foreground-muted">Valores de referência do projeto. O que vale juridicamente e gera cobrança é o contrato.</p>
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="plan_name" label="Plano contratado" :value="$project->plan_name" />
                <x-admin.input name="billing_day" label="Dia de vencimento" type="number" :value="$project->billing_day" min="1" max="28" />
                <x-admin.input name="setup_fee" label="Valor de implantação (R$)" :value="\App\Support\Money::toInput($project->setup_fee_cents)" />
                <x-admin.input name="monthly_fee" label="Valor mensal (R$)" :value="\App\Support\Money::toInput($project->monthly_fee_cents)" />
            </div>
        </x-admin.card>

        <x-admin.card title="Uso interno">
            <x-admin.textarea name="internal_notes" label="Observações internas" :value="$project->internal_notes" help="Nunca exibidas no portal." />
        </x-admin.card>

        <div class="flex gap-2">
            <x-admin.button type="submit" variant="primary">Salvar</x-admin.button>
            <x-admin.button :href="$editing ? route('admin.gestao.projects.show', $project) : route('admin.gestao.projects.index')" variant="ghost">Cancelar</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
