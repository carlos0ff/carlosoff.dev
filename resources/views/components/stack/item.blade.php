@props([
    'name',
    'icon' => null,
    'invert' => false,
    'index' => 0,
])

<li
    style="--reveal-index: {{ $index }}"
    class="experience-reveal flex min-w-0 items-center gap-3 rounded-md border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white px-4 py-3 transition-colors hover:border-primary-color/60"
>
    @if ($icon)
        <img
            src="{{ asset($icon) }}"
            alt=""
            width="20"
            height="20"
            loading="lazy"
            @class(['h-5 w-5 shrink-0 object-contain', 'dark:invert' => $invert])
        >
    @else
        {{-- Sem ícone da tecnologia: ícone padrão (Lucide "code") para manter o mesmo padrão --}}
        <svg data-default-icon xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-primary-color" aria-hidden="true">
            <polyline points="16 18 22 12 16 6"></polyline>
            <polyline points="8 6 2 12 8 18"></polyline>
        </svg>
    @endif

    <span class="truncate text-sm font-semibold">{{ $name }}</span>
</li>
