<x-layouts.portal title="Notificações">
    <x-admin.page-header title="Notificações" subtitle="Avisos enviados sobre seus projetos, documentos e cobranças." />
    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <x-admin.card>
                <p class="font-semibold">{{ $notification->data['subject'] ?? 'Aviso' }} @unless ($notification->read_at)<x-admin.badge tone="primary">nova</x-admin.badge>@endunless</p>
                @foreach ($notification->data['lines'] ?? [] as $line)<p class="mt-1 text-sm text-foreground-secondary">{{ $line }}</p>@endforeach
                @if (! empty($notification->data['action_url']))<a class="mt-2 inline-block text-sm text-primary-hover hover:underline" href="{{ $notification->data['action_url'] }}">{{ $notification->data['action_text'] ?? 'Abrir' }}</a>@endif
                <p class="mt-2 text-xs text-foreground-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
            </x-admin.card>
        @empty
            <x-admin.card><p class="text-sm text-foreground-muted">Nenhuma notificação.</p></x-admin.card>
        @endforelse
    </div>
    <x-admin.pagination :paginator="$notifications" />
</x-layouts.portal>
