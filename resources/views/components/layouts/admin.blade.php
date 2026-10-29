@props(['title' => 'Painel', 'breadcrumbs' => []])
<!DOCTYPE html>
<html lang="pt-BR" class="admin-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | Painel</title>

    @vite(['resources/css/app.css', 'resources/js/admin.js'])

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/laravel-favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen font-incognito antialiased bg-background text-foreground">
    <div class="flex min-h-screen">
        <x-admin.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-admin.header :breadcrumbs="$breadcrumbs" />

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-admin.toast />
    <x-admin.confirm />
</body>
</html>
