{{-- Renderiza um campo definido em `fields()` de um ResourceController. --}}
@props(['field', 'model'])

@php
    $name = $field['name'];
    $type = $field['type'];
    $wide = ($field['wide'] ?? false) || in_array($type, ['textarea', 'lines', 'image'], true);
    $value = $model->{$name};

    if ($type === 'lines') {
        $value = implode("\n", (array) $value);
    } elseif ($type === 'date') {
        $value = $value?->format('Y-m-d');
    } elseif ($value instanceof \BackedEnum) {
        $value = $value->value;
    }

    $span = $wide ? 'md:col-span-2' : '';
@endphp

@switch($type)
    @case('textarea')
    @case('lines')
        <x-admin.textarea :name="$name" :label="$field['label']" :value="$value" :help="$field['help'] ?? null" :required="$field['required'] ?? false" :rows="$field['rows'] ?? 4" class="{{ $span }}" />
        @break

    @case('select')
        <x-admin.select :name="$name" :label="$field['label']" :value="$value" :options="$field['options']" :help="$field['help'] ?? null" :required="$field['required'] ?? false" class="{{ $span }}" />
        @break

    @case('checkbox')
        <x-admin.toggle :name="$name" :label="$field['label']" :checked="(bool) $value" :help="$field['help'] ?? null" class="{{ $span }}" />
        @break

    @case('image')
        <x-admin.file :name="$name" :label="$field['label']" :current="$value" :help="$field['help'] ?? null" class="{{ $span }}" />
        @break

    @default
        <x-admin.input :name="$name" :label="$field['label']" :type="$type" :value="$value" :help="$field['help'] ?? null" :required="$field['required'] ?? false" :min="$type === 'number' ? 0 : null" class="{{ $span }}" />
@endswitch
