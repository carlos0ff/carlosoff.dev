<x-layouts.admin :title="'Chamado #'.$ticket->id" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Chamados' => route('admin.gestao.tickets.index'), '#'.$ticket->id => null]">
    <x-admin.page-header :title="'#'.$ticket->id.' '.$ticket->subject" :subtitle="$ticket->project->name.' — '.$ticket->client->displayName()">
        <x-admin.status :value="$ticket->priority" /><x-admin.status :value="$ticket->status" />
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-admin.card :title="'Aberto por '.($ticket->opener?->name ?? '—').' em '.$ticket->created_at->format('d/m/Y H:i')">
                <p class="whitespace-pre-line text-sm">{{ $ticket->description }}</p>
            </x-admin.card>
            @foreach ($ticket->messages as $message)
                <div @class(['rounded-xl border p-4 text-sm', 'border-warning-border bg-warning-muted' => $message->is_internal, 'border-border bg-card' => ! $message->is_internal])>
                    <p class="mb-1 text-xs text-foreground-muted">{{ $message->author?->name }} · {{ $message->created_at->format('d/m/Y H:i') }} @if ($message->is_internal)· nota interna (invisível ao cliente)@endif</p>
                    <p class="whitespace-pre-line">{{ $message->body }}</p>
                </div>
            @endforeach
            <x-admin.card title="Responder">
                <form method="POST" action="{{ route('admin.gestao.tickets.reply', $ticket) }}" class="space-y-3">
                    @csrf
                    <x-admin.textarea name="body" label="Mensagem" required />
                    <x-admin.toggle name="is_internal" label="Nota interna (não enviar ao cliente)" />
                    <x-admin.select name="status" label="Status" :options="\App\Enums\TicketStatus::options()" :value="$ticket->status->value" />
                    <x-admin.button type="submit" size="sm" variant="primary">Enviar</x-admin.button>
                </form>
            </x-admin.card>
        </div>
        <x-admin.card title="Prazos">
            <x-admin.kv class="!grid-cols-1" :items="[
                'Resposta prevista até' => $ticket->response_due_at?->format('d/m/Y H:i') ?? 'sem SLA contratado',
                'Primeira resposta' => $ticket->first_response_at?->format('d/m/Y H:i'),
                'Solução estimada até' => $ticket->resolution_due_at?->format('d/m/Y H:i') ?? 'sem SLA contratado',
                'Resolvido em' => $ticket->resolved_at?->format('d/m/Y H:i'),
            ]" />
        </x-admin.card>
    </div>
</x-layouts.admin>
