<x-layouts.admin title="Chamados" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Chamados' => null]">
    <x-admin.page-header title="Chamados de suporte" :subtitle="(($counts['open'] ?? 0) + ($counts['in_progress'] ?? 0)).' em aberto · prazo de resposta e de solução acompanhados separadamente'" />
    <x-admin.filters :action="route('admin.gestao.tickets.index')" :current="$current" :counts="$counts" placeholder="—" :tabs="['all' => 'Todos'] + \App\Enums\TicketStatus::options()" />
    @if ($tickets->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhum chamado" description="Os clientes abrem chamados pelo portal." /></x-admin.card>
    @else
        <x-admin.table>
            <thead><tr><th scope="col">Chamado</th><th scope="col" class="admin-col-wide">Projeto</th><th scope="col">Prioridade</th><th scope="col">Primeira resposta</th><th scope="col">Status</th><th scope="col" class="text-right">Ações</th></tr></thead>
            <tbody>
                @foreach ($tickets as $ticket)
                    @php($late = ! $ticket->first_response_at && $ticket->response_due_at?->isPast())
                    <tr>
                        <td data-label="Chamado"><a class="font-semibold transition-colors hover:text-primary-hover" href="{{ route('admin.gestao.tickets.show', $ticket) }}">#{{ $ticket->id }} {{ $ticket->subject }}</a><span class="block text-xs text-foreground-muted">{{ $ticket->created_at->format('d/m/Y H:i') }}</span></td>
                        <td data-label="Projeto" class="admin-col-wide">{{ $ticket->project->name }}<span class="block text-xs text-foreground-muted">{{ $ticket->client->displayName() }}</span></td>
                        <td data-label="Prioridade"><x-admin.status :value="$ticket->priority" /></td>
                        <td data-label="Primeira resposta" class="whitespace-nowrap text-sm">
                            @if ($ticket->first_response_at)
                                <span class="text-success">✓ {{ $ticket->first_response_at->format('d/m H:i') }}</span>
                            @elseif ($ticket->response_due_at)
                                <span @class(['font-semibold text-error' => $late, 'text-foreground-secondary' => ! $late])>{{ $late ? 'Atrasada · ' : 'até ' }}{{ $ticket->response_due_at->format('d/m H:i') }}</span>
                            @else
                                <span class="text-foreground-muted">sem SLA</span>
                            @endif
                        </td>
                        <td data-label="Status"><x-admin.badge :tone="$ticket->status->tone()">● {{ $ticket->status->label() }}</x-admin.badge></td>
                        <td data-label=""><div class="flex justify-end"><x-admin.button size="sm" :href="route('admin.gestao.tickets.show', $ticket)">{{ $ticket->first_response_at ? 'Abrir' : 'Responder' }}</x-admin.button></div></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$tickets" />
    @endif
</x-layouts.admin>
