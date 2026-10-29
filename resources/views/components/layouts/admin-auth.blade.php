@props(['title' => 'Painel Administrativo | José Carlos'])
<!DOCTYPE HTML>
<html lang="pt-BR" class="admin-theme">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/laravel-favicon.svg') }}" />
</head>

{{-- Layout exclusivo das telas de autenticação do admin: sem header, footer, textura ou navegação pública. --}}
<body class="min-h-dvh bg-background text-foreground antialiased">
    {{ $slot }}
</body>
</html>
