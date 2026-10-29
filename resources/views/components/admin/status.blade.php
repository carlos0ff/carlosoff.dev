{{-- Badge para enums com label()/tone() (status do projeto, cobrança, documento...). --}}
@props(['value'])

@if ($value instanceof \BackedEnum && method_exists($value, 'tone'))
    <x-admin.badge :tone="$value->tone()" {{ $attributes }}>{{ $value->label() }}</x-admin.badge>
@else
    <span class="text-foreground-muted">—</span>
@endif
