@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-incognito text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-foreground-secondary">{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
