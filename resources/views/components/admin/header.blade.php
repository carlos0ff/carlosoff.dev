@props(['breadcrumbs' => []])

<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border-subtle bg-header/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" data-sidebar-toggle aria-label="Abrir menu" aria-controls="admin-sidebar" class="grid h-10 w-10 shrink-0 place-items-center rounded-md border border-border bg-secondary text-foreground-secondary transition-colors hover:bg-secondary-hover hover:text-foreground lg:hidden">
        <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <nav aria-label="Você está em" class="min-w-0 flex-1 text-sm text-foreground-secondary">
        <ol class="flex items-center gap-2 overflow-hidden whitespace-nowrap">
            <li class="hidden sm:block"><a href="{{ route('admin.dashboard') }}" class="transition-colors hover:text-foreground">Painel</a></li>
            @foreach ($breadcrumbs as $label => $url)
                <li aria-hidden="true" class="hidden sm:block">/</li>
                <li class="min-w-0 truncate">
                    @if ($url)
                        <a href="{{ $url }}" class="transition-colors hover:text-foreground">{{ $label }}</a>
                    @else
                        <span aria-current="page" class="text-foreground">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
            @if (empty($breadcrumbs))
                <li class="sm:hidden text-foreground">Painel</li>
            @endif
        </ol>
    </nav>

    <span class="hidden shrink-0 items-center gap-2 text-sm sm:flex">
        <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-muted text-xs font-bold text-primary-hover" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <span class="max-w-[10rem] truncate font-medium">{{ auth()->user()->name }}</span>
    </span>
</header>
