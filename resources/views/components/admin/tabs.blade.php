{{-- Abas de navegação por query string (sem JS). Cada aba: chave => rótulo ou [rótulo, contador]. --}}
@props(['tabs', 'current', 'param' => 'aba', 'label' => 'Seções'])

<nav aria-label="{{ $label }}" {{ $attributes->class('mb-6 overflow-x-auto border-b border-border-subtle') }}>
    <ul class="flex min-w-max gap-1">
        @foreach ($tabs as $key => $tab)
            @php([$tabLabel, $badge] = is_array($tab) ? $tab + [1 => null] : [$tab, null])
            <li>
                <a
                    href="{{ request()->fullUrlWithQuery([$param => $key, 'page' => null]) }}"
                    @if ($current === $key) aria-current="page" @endif
                    @class([
                        '-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        'border-primary text-foreground' => $current === $key,
                        'border-transparent text-foreground-secondary hover:border-border-hover hover:text-foreground' => $current !== $key,
                    ])
                >
                    {{ $tabLabel }}
                    @if ($badge)
                        <span @class(['rounded-full px-1.5 text-[11px] tabular-nums', 'bg-primary-muted text-primary-hover' => $current === $key, 'bg-card-hover text-foreground-secondary' => $current !== $key])>{{ $badge }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
