@props(['graph'])
@php($currentYearLabel = now()->format('Y'))

<div style="opacity: 1; transform: none;">
    <div class="mb-8">
        <h2 class="font-incognito text-4xl font-bold tracking-tight">Contribution Graph</h2>
    </div>
</div>

<div style="opacity: 1; transform: none;">
    <div>
        <div id="contribution-widget" data-endpoint="{{ route('github.contributions') }}" data-current-year-label="{{ $currentYearLabel }}" class="flex xl:flex-row flex-col items-start gap-4 w-full" >

            {{-- Gráfico --}}
            <div class="dark:bg-[#202022] bg-secondary-bg border dark:border-zinc-800 border-zinc-200 px-7 py-8 rounded-md w-full min-w-0 xl:flex-1 min-h-[280px]">
                <article class="w-full h-full flex flex-col justify-between min-w-0">

                    {{-- Área do calendário: SVG escala/recalcula largura via resources/js/app.js (initContributionGraph) --}}
                    <div id="calendar-wrapper" class="w-full min-w-0 flex flex-col gap-2">
                        <script type="application/json" id="calendar-data">@json($graph)</script>

                        <svg id="calendar-svg" viewBox="0 0 {{ $graph['width'] }} {{ $graph['height'] }}" preserveAspectRatio="xMinYMin meet" class="react-activity-calendar__calendar transition-opacity duration-300 ease-in-out w-full h-auto block">

                            {{-- Meses --}}
                            <g id="calendar-months" class="react-activity-calendar__legend-month" fill="currentColor">
                                @foreach($graph['months'] as $month)
                                    <text x="{{ $month['x'] }}" dominant-baseline="hanging">{{ $month['label'] }}</text>
                                @endforeach
                            </g>

                            {{-- Contribuições --}}
                            <g id="calendar-squares">
                                @foreach($graph['squares'] as $square)
                                    <rect x="{{ $square['x'] }}" y="{{ $square['y'] }}" width="{{ $square['size'] ?? 20 }}" height="{{ $square['size'] ?? 20 }}" rx="3" ry="3" fill="{{ $square['color'] }}" data-date="{{ $square['date'] }}" data-level="{{ $square['level'] }}" >
                                        <title>{{ $square['count'] }} contribuições em {{ $square['date'] }}</title>
                                    </rect>
                                @endforeach
                            </g>

                        </svg>

                    </div>

                    {{-- Rodapé do gráfico --}}
                    <footer class="react-activity-calendar__footer styles-module_footer__ZQ-Bl">

                        {{-- Quantidade — vem direto de contributionCalendar.totalContributions, nunca calculado aqui --}}
                        <div id="calendar-count" class="react-activity-calendar__count">
                            {{ $graph['total'] }} contributions in {{ $graph['isCurrent'] ? 'the last year' : $graph['selectedYear'] }}
                        </div>

                        <x-github.contribution-legend />
                    </footer>
                </article>
            </div>

            {{-- Botões dos anos --}}
            <div id="year-buttons" class="flex justify-start xl:flex-col flex-row flex-wrap gap-2 xl:flex-shrink-0">
                @foreach($graph['years'] as $year)
                    @php($isActive = $year === $graph['selectedYear'])
                    <button
                        type="button"
                        data-year="{{ $year }}"
                        data-active="{{ $isActive ? '1' : '0' }}"
                        title="View Graph for the year {{ $year === 'current' ? $currentYearLabel : $year }}"
                        class="year-btn rounded-lg text-center px-4 py-2 border border-transparent duration-100 text-sm font-medium
                            @if($isActive)
                                dark:bg-secondary-color bg-secondary-color dark:text-white text-white
                            @else
                                dark:bg-primary-bg bg-zinc-50 dark:text-white text-zinc-800 dark:hover:border-zinc-700 hover:border-zinc-200
                            @endif
                        "
                    >
                        {{ $year === 'current' ? $currentYearLabel : $year }}
                    </button>
                @endforeach
            </div>

        </div>
    </div>
</div>
