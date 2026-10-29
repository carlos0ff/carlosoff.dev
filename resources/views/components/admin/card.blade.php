@props(['title' => null, 'padding' => true])

<section {{ $attributes->class('rounded-xl border border-border bg-card') }}>
    @if ($title)
        <header class="flex items-center justify-between gap-3 border-b border-border-subtle px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-foreground-secondary">{{ $title }}</h2>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5' => $padding])>{{ $slot }}</div>
</section>
