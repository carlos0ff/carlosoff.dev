@props(['title' => 'Portal do cliente'])
<!DOCTYPE html>
<html lang="pt-BR" class="admin-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | Portal do cliente</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/laravel-favicon.svg') }}">
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    @php($nav = ['portal.dashboard' => 'Início', 'portal.documents.index' => 'Documentos', 'portal.charges.index' => 'Cobranças', 'portal.scope.index' => 'Solicitações', 'portal.tickets.index' => 'Chamados', 'portal.notifications.index' => 'Notificações'])
    <header class="sticky top-0 z-20 border-b border-border-subtle bg-header/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
            <a href="{{ route('portal.dashboard') }}" class="font-semibold">{{ auth()->user()->client?->displayName() }}</a>
            <nav aria-label="Portal" class="order-3 flex w-full gap-1 overflow-x-auto text-sm sm:order-none sm:w-auto sm:flex-1">
                @foreach ($nav as $route => $label)
                    <a href="{{ route($route) }}" @class(['whitespace-nowrap rounded-md px-3 py-1.5 transition-colors', 'bg-primary-muted text-primary-hover' => request()->routeIs($route), 'text-foreground-secondary hover:bg-card-hover hover:text-foreground' => ! request()->routeIs($route)])>
                        {{ $label }}@if ($route === 'portal.notifications.index' && ($unread = auth()->user()->unreadNotifications()->count())) <span class="ml-1 rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{{ $unread }}</span>@endif
                    </a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('portal.logout') }}" class="ml-auto">@csrf<button type="submit" class="text-sm text-foreground-secondary hover:text-foreground">Sair ({{ auth()->user()->name }})</button></form>
        </div>
    </header>
    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6">{{ $slot }}</main>
    <x-admin.toast />
    <x-admin.confirm />
</body>
</html>
