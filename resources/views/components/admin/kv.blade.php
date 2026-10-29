{{-- Lista "rótulo: valor" para páginas de detalhe. --}}
@props(['items' => []])

<dl {{ $attributes->class('grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2') }}>
    @foreach ($items as $label => $value)
        <div class="min-w-0">
            <dt class="text-xs font-medium uppercase tracking-wide text-foreground-muted">{{ $label }}</dt>
            <dd class="mt-0.5 [overflow-wrap:anywhere]">{!! filled($value) ? $value : '<span class="text-foreground-muted">—</span>' !!}</dd>
        </div>
    @endforeach
</dl>
