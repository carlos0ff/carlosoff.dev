@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // 1 … (atual-1) atual (atual+1) … última
        $pages = collect(range(1, $last))
            ->filter(fn (int $page) => $page === 1 || $page === $last || abs($page - $current) <= 1)
            ->values();

        $base = 'h-10 min-w-10 items-center justify-center rounded-md border px-3 text-sm font-semibold transition-colors';
        $idle = 'dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white hover:border-primary-color/60 hover:text-primary-color';
        $disabled = 'dark:border-zinc-800 border-zinc-200 opacity-40 cursor-not-allowed';
    @endphp

    <nav role="navigation" aria-label="Paginação dos projetos" class="mt-12 flex flex-wrap items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex {{ $base }} {{ $disabled }}">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex {{ $base }} {{ $idle }}">Anterior</a>
        @endif

        @foreach ($pages as $page)
            @if (! $loop->first && $page - $pages[$loop->index - 1] > 1)
                <span aria-hidden="true" class="hidden px-1 text-zinc-500 sm:inline">…</span>
            @endif

            @if ($page === $current)
                <span aria-current="page" class="inline-flex {{ $base }} border-primary-color bg-primary-color text-white">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" aria-label="Ir para a página {{ $page }}" class="{{ $base }} {{ $idle }} hidden sm:inline-flex">{{ $page }}</a>
            @endif
        @endforeach

        <span class="text-sm text-zinc-500 sm:hidden">de {{ $last }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex {{ $base }} {{ $idle }}">Próxima</a>
        @else
            <span aria-disabled="true" class="inline-flex {{ $base }} {{ $disabled }}">Próxima</span>
        @endif
    </nav>
@endif
