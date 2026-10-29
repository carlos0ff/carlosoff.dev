<x-layouts.admin title="Modelos de documento" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Modelos' => null]">
    <x-admin.page-header title="Modelos de documento" subtitle="Textos com variáveis preenchidas a partir dos cadastros. Precisam de revisão jurídica antes de irem para assinatura." />

    <x-admin.table>
        <thead><tr><th>Modelo</th><th>Tipo</th><th>Versão</th><th>Revisão jurídica</th></tr></thead>
        <tbody>
            @foreach ($templates as $template)
                <tr>
                    <td data-label="Modelo"><a class="font-semibold hover:text-primary-hover" href="{{ route('admin.gestao.templates.edit', $template) }}">{{ $template->name }}</a> @unless ($template->is_active)<x-admin.badge>inativo</x-admin.badge>@endunless</td>
                    <td data-label="Tipo">{{ $template->document_type->label() }}</td>
                    <td data-label="Versão">v{{ $template->version }}</td>
                    <td data-label="Revisão jurídica">
                        @if ($template->isLegallyReviewed())
                            <x-admin.badge tone="success">Revisado em {{ $template->legal_reviewed_at->format('d/m/Y') }}</x-admin.badge>
                        @else
                            <x-admin.badge tone="warning">Pendente — só gera minutas</x-admin.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
</x-layouts.admin>
