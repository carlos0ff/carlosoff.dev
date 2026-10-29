@php([$open, $close] = ['{'.'{ ', ' }'.'}'])
<x-layouts.admin :title="$template->name" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Modelos' => route('admin.gestao.templates.index'), $template->name => null]">
    <x-admin.page-header :title="$template->name" :subtitle="$template->document_type->label().' · v'.$template->version" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.gestao.templates.update', $template) }}" class="space-y-4 lg:col-span-2">
            @csrf @method('PUT')
            <x-admin.card title="Texto do modelo">
                <div class="space-y-4">
                    <x-admin.input name="name" label="Nome" :value="$template->name" required />
                    <x-admin.textarea name="body" label="Corpo (HTML simples)" :value="$template->body" rows="28" class="font-mono" :help="'Use variáveis no formato '.$open.'cliente.razao_social'.$close.'. Campos sem valor ficam destacados no documento gerado.'" />
                    <x-admin.toggle name="is_active" label="Modelo ativo" :checked="$template->is_active" />
                </div>
            </x-admin.card>
            @can('gestao.admin')<x-admin.button type="submit" variant="primary">Salvar (invalida revisão se o texto mudar)</x-admin.button>@endcan
        </form>

        <div class="space-y-6">
            <x-admin.card title="Revisão jurídica">
                @if ($template->isLegallyReviewed())
                    <p class="text-sm text-success">Revisado em {{ $template->legal_reviewed_at->format('d/m/Y H:i') }} por {{ $template->reviewer?->name }}.</p>
                    <p class="mt-1 text-sm text-foreground-secondary">{{ $template->legal_review_notes }}</p>
                @else
                    <p class="mb-3 text-sm text-warning">Enquanto não for revisado, este modelo só gera minutas (com marca d'água) e não pode ser enviado para assinatura.</p>
                    @can('gestao.admin')
                        <form method="POST" action="{{ route('admin.gestao.templates.approve', $template) }}" class="space-y-3">
                            @csrf
                            <x-admin.input name="legal_review_notes" label="Identificação da revisão" help="Ex.: Revisado por Dra. Fulana (OAB/XX 000) em parecer de 10/10/2026" required />
                            <x-admin.toggle name="confirm" label="Confirmo que este texto foi revisado por profissional jurídico habilitado" />
                            <x-admin.button type="submit" size="sm">Registrar revisão</x-admin.button>
                        </form>
                    @endcan
                @endif
            </x-admin.card>

            <x-admin.card title="Variáveis disponíveis">
                <ul class="space-y-1 font-mono text-xs text-foreground-secondary">
                    @foreach (['data.hoje', 'empresa.nome', 'empresa.documento', 'empresa.email', 'empresa.cidade', 'cliente.razao_social', 'cliente.nome_fantasia', 'cliente.documento', 'cliente.email', 'cliente.endereco', 'cliente.responsavel', 'cliente.responsavel_documento', 'projeto.nome', 'projeto.descricao', 'projeto.objetivo', 'projeto.tecnologias', 'projeto.dominio', 'projeto.url_homologacao', 'projeto.url_producao', 'projeto.entrega_prevista', 'contrato.numero', 'contrato.objeto', 'contrato.escopo', 'contrato.entregaveis', 'contrato.valor_implantacao', 'contrato.mensalidade', 'contrato.forma_pagamento', 'contrato.condicoes_pagamento', 'contrato.dia_vencimento', 'contrato.inicio', 'contrato.fim', 'contrato.renovacao', 'contrato.sla', 'contrato.clausula.*', 'politica.tolerancia_dias', 'politica.aviso_previo_dias', 'politica.forma_restricao', 'politica.reativacao', 'politica.clausula', 'validacao.versao', 'validacao.ambiente', 'validacao.funcionalidades', 'validacao.testes', 'validacao.pendencias', 'validacao.resultado', 'solicitacao.titulo', 'solicitacao.descricao', 'solicitacao.horas', 'solicitacao.valor', 'solicitacao.prazo_dias', 'solicitacao.criterios', 'dominio.nome', 'dominio.titular', 'dominio.registrador', 'dominio.expiracao', 'hospedagem.provedor', 'hospedagem.recursos', 'hospedagem.backup', 'hospedagem.migracao'] as $variable)
                        <li>{{ $open.$variable.$close }}</li>
                    @endforeach
                </ul>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
