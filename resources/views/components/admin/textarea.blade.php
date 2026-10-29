@props(['name', 'label', 'value' => null, 'help' => null, 'required' => false, 'rows' => 4])

@php($key = str_replace(['[', ']'], ['.', ''], $name))

<x-admin.field :name="$name" :label="$label" :help="$help" :required="$required" {{ $attributes->only('class') }}>
    <textarea
        id="f-{{ $key }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        {{ $attributes->except('class')->class([
            'w-full rounded-md border bg-background px-3 py-2 text-sm leading-relaxed outline-none transition-colors focus:border-primary disabled:cursor-not-allowed disabled:text-foreground-disabled',
            'border-border hover:border-border-hover' => ! $errors->has($key),
            'border-error' => $errors->has($key),
        ]) }}
    >{{ old($key, $value) }}</textarea>
</x-admin.field>
