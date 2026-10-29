@props([
    'company',
    'role' => null,
    'period' => null,
    'description' => null,
    'logo' => null,
    'url' => null,
    'skills' => [],
    'current' => false,
    'location' => null,
    'responsibilities' => [],
    'results' => [],
    'connector' => 'full',
    'index' => 0,
])

@php
    $words = \Illuminate\Support\Str::of($company)->before(' / ')->explode(' ')->filter()->values();
    $initials = $words->count() > 1
        ? $words->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('')
        : mb_substr((string) $words->first(), 0, 2);
    $initials = mb_strtoupper($initials);

    // Logos são JPEG/PNG com fundo claro: ocupam a caixa inteira, sem padding.
    $logoClasses = 'grid place-items-center border dark:border-zinc-800 border-zinc-200 min-h-[80px] min-w-[80px] h-20 w-20 rounded-md overflow-clip relative shrink-0 '
        .($logo ? 'bg-white' : 'dark:bg-primary-bg bg-secondary-bg p-2');
@endphp

<article
    style="--reveal-index: {{ $index }}"
    @class([
        'experience-reveal relative flex items-start lg:gap-x-6 gap-x-4 min-w-0',
        // Linha vertical que sai do logo e alcança o logo do próximo item da mesma coluna.
        'before:absolute before:top-[5rem] before:left-10 before:w-px before:h-[calc(100%-2.5rem)] dark:before:bg-zinc-800 before:bg-zinc-200' => $connector !== 'none',
        // No desktop (2 colunas) o penúltimo item é o último da sua coluna: sem linha.
        'lg:before:hidden' => $connector === 'lg-hidden',
    ])
>
    @if ($url)
        <a href="{{ $url }}" rel="noopener" target="_blank" class="{{ $logoClasses }}" aria-label="{{ $company }}">
            @if ($logo)
                <img src="{{ asset($logo) }}" alt="{{ $company }} logo" loading="lazy" width="80" height="80" class="h-full w-full object-contain">
            @else
                <span class="text-lg font-semibold tracking-wide dark:text-zinc-400 text-zinc-600" aria-hidden="true">{{ $initials }}</span>
            @endif
        </a>
    @else
        <div class="{{ $logoClasses }}">
            @if ($logo)
                <img src="{{ asset($logo) }}" alt="{{ $company }} logo" loading="lazy" width="80" height="80" class="h-full w-full object-contain">
            @else
                <span class="text-lg font-semibold tracking-wide dark:text-zinc-400 text-zinc-600" aria-hidden="true">{{ $initials }}</span>
            @endif
        </div>
    @endif

    <div class="flex flex-col items-start min-w-0">
        <h3 class="text-xl font-semibold">
            @if ($url)
                <a href="{{ $url }}" rel="noopener" target="_blank" class="hover:text-primary-color transition-colors">{{ $company }}</a>
            @else
                {{ $company }}
            @endif
        </h3>

        @if ($role)
            <p class="dark:text-zinc-300 text-zinc-700">{{ $role }}</p>
        @endif

        @if ($period)
            <time @class([
                'text-sm mt-2 tracking-widest uppercase',
                'text-primary-color' => $current,
                'text-zinc-500' => ! $current,
            ])>{{ $period }}</time>
        @endif

        @if ($location)
            <p data-experience-location class="mt-1 text-sm dark:text-zinc-500 text-zinc-500">{{ $location }}</p>
        @endif

        @if ($description)
            <p class="tracking-tight dark:text-zinc-400 text-zinc-600 my-4 leading-relaxed whitespace-pre-line">{{ $description }}</p>
        @endif

        @foreach (['Responsabilidades' => $responsibilities, 'Resultados' => $results] as $heading => $entries)
            @if (count($entries) > 0)
                <div class="mb-4">
                    <h4 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ $heading }}</h4>
                    <ul class="mt-2 space-y-1 dark:text-zinc-400 text-zinc-600">
                        @foreach ($entries as $entry)
                            <li class="flex gap-3 leading-relaxed">
                                <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-color"></span>
                                <span>{{ $entry }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        @if (count($skills) > 0)
            <ul class="mb-4 flex flex-wrap items-center gap-2" aria-label="Competências">
                @foreach ($skills as $skill)
                    <li class="rounded-full border dark:border-zinc-800 border-zinc-200 px-3 py-1 text-xs dark:text-zinc-400 text-zinc-600">{{ $skill }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
