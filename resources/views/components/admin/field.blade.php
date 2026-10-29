@props(['name', 'label', 'help' => null, 'required' => false, 'for' => null])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $for ??= 'f-'.$key;
@endphp

<div {{ $attributes->class('min-w-0') }}>
    <label for="{{ $for }}" class="mb-1.5 block text-sm font-medium">
        {{ $label }}@if ($required)<span class="text-primary" aria-hidden="true"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($help)
        <p class="mt-1.5 text-xs text-foreground-muted [overflow-wrap:anywhere]">{{ $help }}</p>
    @endif

    @error($key)
        <p role="alert" class="mt-1.5 text-xs font-medium text-error">{{ $message }}</p>
    @enderror
</div>
