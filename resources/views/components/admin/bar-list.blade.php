{{-- Ranking com barras proporcionais. Cada item: label, value e (opcional) href. --}}
@props(['items', 'unit' => 'acessos', 'empty' => 'Sem dados no período.', 'prefix' => ''])

@php
    $items = collect($items);
    $max = max(1, (int) $items->max('value'));
@endphp

@if ($items->isEmpty())
    <p class="py-6 text-center text-sm text-foreground-muted">{{ $empty }}</p>
@else
    <ol {{ $attributes->class('space-y-3') }}>
        @foreach ($items as $item)
            <li class="min-w-0">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <span class="flex min-w-0 items-baseline gap-2">
                        <span class="w-5 shrink-0 text-xs tabular-nums text-foreground-muted">{{ $loop->iteration }}.</span>
                        @if (! empty($item['href']))
                            <a href="{{ $item['href'] }}" class="truncate font-medium transition-colors hover:text-primary-hover">{{ $item['label'] }}</a>
                        @else
                            <span class="truncate font-medium">{{ $item['label'] }}</span>
                        @endif
                    </span>
                    <span class="shrink-0 tabular-nums text-foreground-secondary">{{ $prefix }}{{ number_format($item['value'], 0, ',', '.') }}</span>
                </div>
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-card-hover" aria-hidden="true">
                    <div @class(['h-full rounded-full', 'bg-primary' => $loop->first, 'bg-foreground-disabled' => ! $loop->first]) style="width: {{ max(2, round($item['value'] / $max * 100)) }}%"></div>
                </div>
            </li>
        @endforeach
    </ol>
@endif
