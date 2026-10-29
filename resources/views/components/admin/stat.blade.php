@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'icon' => null,
    'accent' => 'primary', // primary | blue | green | red | amber | purple | cyan
])

@php
    $accentMap = [
        'primary' => 'bg-primary-muted text-primary',
        'blue'    => 'bg-info-muted text-info',
        'green'   => 'bg-success-muted text-success',
        'red'     => 'bg-error-muted text-error',
        'amber'   => 'bg-warning-muted text-warning',
        'purple'  => 'bg-[rgb(168_85_247/0.12)] text-[#a855f7]',
        'cyan'    => 'bg-[rgb(6_182_212/0.12)] text-[#0891b2]',
    ];
    $accentClass = $accentMap[$accent] ?? $accentMap['primary'];

    $icons = [
        'folder'     => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
        'briefcase'  => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'cpu'        => '<rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2M15 20v2M2 15h2M2 9h2M20 15h2M20 9h2M9 2v2M9 20v2"/>',
        'eye'        => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'graduation' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'calendar'   => '<path d="M8 2v4M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'clock'      => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'layers'     => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65M22 12.65l-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
    ];
    $svg = $icons[$icon] ?? null;
@endphp

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['block rounded-xl border border-border bg-card p-5 transition-colors', 'hover:border-border-hover hover:bg-card-hover' => $href]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-foreground-secondary">{{ $label }}</p>
        @if ($svg)
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $accentClass }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svg !!}</svg>
            </span>
        @endif
    </div>
    <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-foreground">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-foreground-muted">{{ $hint }}</p>
    @endif
</{{ $href ? 'a' : 'div' }}>
