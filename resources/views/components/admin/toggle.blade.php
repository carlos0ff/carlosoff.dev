{{-- Interruptor (checkbox). Desmarcado = campo ausente no POST, tratado como `false` no backend. --}}
@props(['name', 'label', 'checked' => false, 'help' => null])

@php($key = str_replace(['[', ']'], ['.', ''], $name))

<div {{ $attributes->class('min-w-0') }}>
    <label for="f-{{ $key }}" class="flex cursor-pointer items-center gap-3">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="f-{{ $key }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) old($key, $checked)) class="peer sr-only">
        <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-transform peer-checked:bg-primary peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-hover bg-border-hover"></span>
        <span class="text-sm font-medium">{{ $label }}</span>
    </label>

    @if ($help)
        <p class="mt-1.5 pl-14 text-xs text-foreground-muted [overflow-wrap:anywhere]">{{ $help }}</p>
    @endif

    @error($key)
        <p role="alert" class="mt-1.5 pl-14 text-xs font-medium text-error">{{ $message }}</p>
    @enderror
</div>
