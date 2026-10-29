{{-- Gráfico de linha em SVG, calculado no servidor a partir de valores já agregados no banco. --}}
@props(['labels' => [], 'values' => [], 'secondary' => [], 'unit' => 'acessos', 'unitSingular' => 'acesso', 'prefix' => '', 'secondaryLabel' => null, 'ariaLabel' => 'Gráfico de acessos ao longo do tempo'])

@php
    $width = 640;
    $height = 240;
    $left = 44;
    $right = 12;
    $top = 14;
    $bottom = 28;
    $count = count($values);
    $max = max(1, ...($count ? array_merge($values, $secondary) : [0]));

    // Escala "bonita": arredonda o máximo para 1, 2, 5 × 10^n.
    $magnitude = 10 ** floor(log10($max));
    $niceMax = max(4, $magnitude * collect([1, 2, 5, 10])->first(fn ($step) => $max <= $step * $magnitude));
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;

    $x = fn (int $i) => $left + ($count > 1 ? $plotWidth * $i / ($count - 1) : $plotWidth / 2);
    $y = fn (int|float $v) => $top + $plotHeight - ($plotHeight * $v / $niceMax);

    $points = collect($values)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1));
    $line = $points->implode(' ');
    $secondaryLine = collect($secondary)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ');
    $area = $count ? $left.','.($top + $plotHeight).' '.$line.' '.round($x($count - 1), 1).','.($top + $plotHeight) : '';

    $ticks = collect(range(0, 4))->map(fn ($i) => $niceMax * $i / 4);
    $labelEvery = max(1, (int) ceil($count / 8));
    $format = fn ($n) => $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',').'k' : (string) (int) $n;
@endphp

<figure {{ $attributes }}>
    <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $ariaLabel }}" class="h-auto w-full">
        @foreach ($ticks as $tick)
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ round($y($tick), 1) }}" y2="{{ round($y($tick), 1) }}" class="stroke-border" stroke-dasharray="{{ $tick == 0 ? '0' : '3 4' }}" />
            <text x="{{ $left - 8 }}" y="{{ round($y($tick), 1) + 4 }}" text-anchor="end" class="fill-foreground-muted text-[10px]">{{ $format($tick) }}</text>
        @endforeach

        @if ($count)
            <polygon points="{{ $area }}" class="fill-primary/10" />
            @if ($secondaryLine !== '')
                <polyline points="{{ $secondaryLine }}" fill="none" class="stroke-foreground-muted" stroke-width="1.5" stroke-dasharray="5 4" stroke-linejoin="round" />
            @endif
            <polyline points="{{ $line }}" fill="none" class="stroke-primary" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

            @foreach ($values as $i => $value)
                @if ($count <= 45)
                    <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($value), 1) }}" r="3" class="fill-primary-hover" />
                @endif
                {{-- Alvo invisível maior para o tooltip nativo (título) --}}
                <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($value), 1) }}" r="7" fill="transparent"><title>{{ $labels[$i] }}: {{ $prefix }}{{ $value }} {{ $value === 1 ? $unitSingular : $unit }}@if ($secondaryLabel && isset($secondary[$i])) · {{ $secondaryLabel }}: {{ $prefix }}{{ $secondary[$i] }}@endif</title></circle>

                @if ($i % $labelEvery === 0)
                    <text x="{{ round($x($i), 1) }}" y="{{ $height - 8 }}" text-anchor="middle" class="fill-foreground-muted text-[10px]">{{ $labels[$i] }}</text>
                @endif
            @endforeach
        @endif
    </svg>
</figure>
