@props(['name', 'label', 'options' => [], 'value' => null, 'help' => null, 'required' => false])

@php($key = str_replace(['[', ']'], ['.', ''], $name))

<x-admin.field :name="$name" :label="$label" :help="$help" :required="$required" {{ $attributes->only('class') }}>
    <select
        id="f-{{ $key }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->except('class')->class([
            'w-full rounded-md border bg-background px-3 py-2 text-sm outline-none transition-colors focus:border-primary disabled:cursor-not-allowed disabled:text-foreground-disabled',
            'border-border hover:border-border-hover' => ! $errors->has($key),
            'border-error' => $errors->has($key),
        ]) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($key, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-admin.field>
