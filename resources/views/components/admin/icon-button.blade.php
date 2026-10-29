@props(['label', 'href' => null, 'type' => 'submit', 'danger' => false, 'active' => false])

@php
    $classes = 'grid h-8 w-8 place-items-center rounded-md border text-sm transition-colors disabled:cursor-not-allowed disabled:opacity-40 '
        .'border-border bg-secondary text-foreground-secondary '
        .($danger ? 'hover:border-error-border hover:bg-error-muted hover:text-error' : 'hover:border-border-hover hover:bg-secondary-hover hover:text-foreground')
        .($active ? ' !border-primary-border !bg-primary-muted !text-primary' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
