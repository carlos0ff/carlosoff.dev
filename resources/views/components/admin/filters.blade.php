{{-- Busca + abas de status. Mantém os demais parâmetros da query (ordenação) ao filtrar. --}}
@props(['action', 'search' => '', 'tabs' => [], 'current' => 'all', 'counts' => [], 'placeholder' => 'Pesquisar...', 'keep' => []])

<div class="mb-4 space-y-3">
    <form method="GET" action="{{ $action }}" role="search" class="flex flex-wrap gap-2">
        @foreach ($keep as $name => $value)
            @if (filled($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        @if ($current !== 'all')
            <input type="hidden" name="status" value="{{ $current }}">
        @endif

        <label class="relative min-w-0 flex-1 sm:max-w-sm">
            <span class="sr-only">Pesquisar</span>
            <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-foreground-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ $placeholder }}" class="w-full rounded-md border border-border bg-card py-2 pl-9 pr-3 text-sm outline-none transition-colors hover:border-border-hover focus:border-primary">
        </label>
        <x-admin.button type="submit" size="md">Buscar</x-admin.button>
        @if ($search !== '')
            <x-admin.button :href="$action.'?'.http_build_query(array_filter(['status' => $current !== 'all' ? $current : null] + $keep))" variant="ghost">Limpar</x-admin.button>
        @endif
    </form>

    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filtrar por status">
        @foreach ($tabs as $key => $label)
            <a
                href="{{ $action.'?'.http_build_query(array_filter(['status' => $key !== 'all' ? $key : null, 'q' => $search ?: null] + $keep)) }}"
                role="tab"
                aria-selected="{{ $current === $key ? 'true' : 'false' }}"
                @class([
                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                    'border-primary-border bg-primary-muted text-primary-hover' => $current === $key,
                    'border-border text-foreground-secondary hover:border-border-hover hover:bg-card-hover hover:text-foreground' => $current !== $key,
                ])
            >{{ $label }} <span class="tabular-nums opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>
</div>
