<x-layouts.portal title="Chamados">
    <x-admin.page-header title="Chamados" subtitle="Abra chamados pela página de cada projeto." />
    <x-admin.table>
        <thead><tr><th>#</th><th>Assunto</th><th>Projeto</th><th>Prioridade</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td data-label="#">{{ $ticket->id }}</td>
                    <td data-label="Assunto"><a class="font-semibold hover:text-primary-hover" href="{{ route('portal.tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                    <td data-label="Projeto">{{ $ticket->project->name }}</td>
                    <td data-label="Prioridade"><x-admin.status :value="$ticket->priority" /></td>
                    <td data-label="Status"><x-admin.status :value="$ticket->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-foreground-muted">Nenhum chamado.</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <x-admin.pagination :paginator="$tickets" />
</x-layouts.portal>
