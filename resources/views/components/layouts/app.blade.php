@props(['title' => 'José Carlos | Backend Developer PHP & Laravel', 'description' => null, 'image' => null, 'ogType' => 'website', 'ogTitle' => null, 'ogDescription' => null])
<!DOCTYPE HTML>
<html lang="pt-BR" class="dark">
<head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <title>{{ $title }}</title>
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($description)
        <meta name="description" content="{{ $description }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $ogTitle ?: $title }}">
        <meta property="og:description" content="{{ $ogDescription ?: $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($image)
            <meta property="og:image" content="{{ url($image) }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
    @endif

    {{-- Core Stylesheet --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/laravel-favicon.svg') }}" />

    {{-- Lucide Icons & Google Fonts --}}
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{
            background-image: url('{{ asset('image/texture.png') }}') !important;
            background-size: 200px 200px;
            background-position: 0 0;
        }
        
         .laravel-svg .path {
            stroke: url(#paint-linear);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 12 8;
            animation: draw-lines 6s linear infinite, glow-lines 2s ease-in-out infinite alternate;
        }

        .laravel-svg .path {
            stroke: url(#paint-linear);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 1800;
            stroke-dashoffset: 1800;

            animation: svg-draw 5s ease-in-out infinite, svg-glow 2s ease-in-out infinite alternate;
        }

        @keyframes svg-draw {
            0% {
                stroke-dashoffset: 1800;
            }

            55% {
                stroke-dashoffset: 0;
            }

            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes svg-glow {
            0% {
                filter: drop-shadow(0 0 0px #FF2D20);
                stroke-opacity: .35;
            }

            100% {
                filter: drop-shadow(0 0 7px #FF2D20);
                stroke-opacity: .9;
            }
        }
    </style>
</head>

<body class=" dark:bg-zinc-900 bg-white dark:text-white text-zinc-700">

<x-layout.header />

<main class="max-w-6xl mx-auto md:px-16 px-6">
    {{ $slot }}
</main>


</body>
</html>
