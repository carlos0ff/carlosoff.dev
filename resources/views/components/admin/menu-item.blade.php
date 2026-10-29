@props(['href' => null, 'type' => 'submit', 'danger' => false])

@php
    $classes = 'block w-full rounded-md px-3 py-2 text-left text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40 '
        .($danger ? 'text-error hover:bg-error-muted hover:text-primary-hover' : 'text-foreground-secondary hover:bg-card-hover hover:text-foreground');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
