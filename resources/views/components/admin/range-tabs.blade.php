{{-- Seletor de período (ou de granularidade). Preserva os outros parâmetros da query. --}}
@props(['options', 'current', 'param' => 'range', 'label' => 'Período'])

<div role="group" aria-label="{{ $label }}" {{ $attributes->class('inline-flex flex-wrap gap-1 rounded-lg border border-border bg-card p-1') }}>
    @foreach ($options as $key => $optionLabel)
        <a
            href="{{ request()->fullUrlWithQuery([$param => $key]) }}"
            @if ($current === $key) aria-current="true" @endif
            @class([
                'rounded-md px-3 py-1.5 text-xs font-semibold transition-colors',
                'bg-primary-muted text-primary-hover ring-1 ring-inset ring-primary-border' => $current === $key,
                'text-foreground-secondary hover:bg-card-hover hover:text-foreground' => $current !== $key,
            ])
        >{{ $optionLabel }}</a>
    @endforeach
</div>
