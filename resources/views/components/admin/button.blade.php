@props(['variant' => 'secondary', 'href' => null, 'type' => 'button', 'size' => 'md'])

@php
    $classes = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md border font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50 '
        .($size === 'sm' ? 'px-3 py-1.5 text-xs ' : 'px-4 py-2 text-sm ')
        .match ($variant) {
            'primary' => 'border-primary bg-primary text-primary-foreground hover:border-primary-hover hover:bg-primary-hover active:border-primary-active active:bg-primary-active',
            'danger' => 'border-error-border text-error hover:border-error hover:bg-error hover:text-primary-foreground active:bg-primary-active',
            'ghost' => 'border-transparent text-foreground-secondary hover:bg-card-hover hover:text-foreground active:bg-secondary-hover',
            default => 'border-border bg-secondary text-secondary-foreground hover:border-border-hover hover:bg-secondary-hover active:bg-card-hover',
        };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        <span data-button-spinner aria-hidden="true" class="hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
        {{ $slot }}
    </button>
@endif
