@props(['title', 'description' => null])

<div {{ $attributes->class('flex flex-col items-center gap-3 px-6 py-14 text-center') }}>
    <span class="grid h-12 w-12 place-items-center rounded-full bg-card-hover text-foreground-muted" aria-hidden="true">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
    </span>
    <p class="font-semibold">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-foreground-secondary">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
