@props(['paginator'])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-foreground-secondary">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="rounded-md border border-border-subtle px-3 py-1.5 text-foreground-disabled">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-md border border-border bg-secondary px-3 py-1.5 text-secondary-foreground transition-colors hover:border-border-hover hover:bg-secondary-hover active:bg-card-hover">Anterior</a>
            @endif

            <span class="px-2 tabular-nums text-foreground-secondary">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-md border border-border bg-secondary px-3 py-1.5 text-secondary-foreground transition-colors hover:border-border-hover hover:bg-secondary-hover active:bg-card-hover">Próxima</a>
            @else
                <span aria-disabled="true" class="rounded-md border border-border-subtle px-3 py-1.5 text-foreground-disabled">Próxima</span>
            @endif
        </div>
    </nav>
@endif
