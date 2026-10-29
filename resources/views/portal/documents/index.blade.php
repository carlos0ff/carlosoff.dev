<x-layouts.portal title="Documentos">
    <x-admin.page-header title="Documentos" subtitle="Contratos, termos e aceites dos seus projetos." />
    <x-admin.table>
        <thead><tr><th>Documento</th><th>Projeto</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td data-label="Documento"><a class="font-semibold hover:text-primary-hover" href="{{ route('portal.documents.show', $document) }}">{{ $document->title }}</a> <span class="text-xs text-foreground-muted">v{{ $document->version }}</span></td>
                    <td data-label="Projeto">{{ $document->project->name }}</td>
                    <td data-label="Status"><x-admin.status :value="$document->status" /></td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-foreground-muted">Nenhum documento.</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <x-admin.pagination :paginator="$documents" />
</x-layouts.portal>
