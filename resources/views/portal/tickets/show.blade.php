<x-layouts.portal :title="'Chamado #'.$ticket->id">
    <x-admin.page-header :title="'#'.$ticket->id.' '.$ticket->subject" :subtitle="$ticket->project->name"><x-admin.status :value="$ticket->status" /></x-admin.page-header>
    <div class="space-y-4">
        <x-admin.card :title="'Aberto em '.$ticket->created_at->format('d/m/Y H:i')">
            <p class="whitespace-pre-line text-sm">{{ $ticket->description }}</p>
            @if ($ticket->response_due_at)<p class="mt-3 text-xs text-foreground-muted">Primeira resposta prevista até {{ $ticket->response_due_at->format('d/m/Y H:i') }}. O prazo de solução é uma estimativa e depende da complexidade.</p>@endif
        </x-admin.card>
        @foreach ($ticket->messages as $message)
            <div class="rounded-xl border border-border bg-card p-4 text-sm"><p class="mb-1 text-xs text-foreground-muted">{{ $message->author?->name }} · {{ $message->created_at->format('d/m/Y H:i') }}</p><p class="whitespace-pre-line">{{ $message->body }}</p></div>
        @endforeach
        <x-admin.card title="Responder">
            <form method="POST" action="{{ route('portal.tickets.reply', $ticket) }}" class="space-y-3">@csrf<x-admin.textarea name="body" label="Mensagem" required /><x-admin.button type="submit" size="sm">Enviar</x-admin.button></form>
        </x-admin.card>
    </div>
</x-layouts.portal>
