@props(['tone' => 'muted'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium',
    'border-success-border bg-success-muted text-success' => $tone === 'success',
    'border-foreground-muted/30 bg-foreground-muted/10 text-foreground-secondary' => $tone === 'muted',
    'border-warning-border bg-warning-muted text-warning' => $tone === 'warning',
    'border-primary-border bg-primary-muted text-primary-hover' => $tone === 'primary',
    'border-error-border bg-error-muted text-primary-hover' => $tone === 'danger',
    'border-info-border bg-info-muted text-info' => $tone === 'info',
]) }}>{{ $slot }}</span>
