@props([
    'title' => 'José Carlos — Desenvolvedor Backend PHP & Laravel',
    'description' => 'Desenvolvedor backend em PHP e Laravel. Do conceito ao deploy, com código que se explica.',
    'image' => null,
    'ogType' => 'website',
    'active' => 'home',
])
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#09090b">

    <title>{{ $title }}</title>
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($description)
        <meta name="description" content="{{ $description }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/laravel-favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root { --site-bg: #0a0a0c; --site-accent: #4d8dff; }
        .font-display { font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; }
        .font-mono2   { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        body.site { background-image: none !important; background-color: var(--site-bg); font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif; }
        .site ::selection { background: rgba(77,141,255,0.3); color: #fff; }
        .site :focus-visible { outline: 2px solid var(--site-accent); outline-offset: 2px; }
        .site-reveal { opacity: 0; transform: translateY(16px); animation: site-reveal .6s ease-out forwards; animation-delay: calc(var(--i, 0) * 80ms); }
        @keyframes site-reveal { to { opacity: 1; transform: none; } }

        /* Reveal ao rolar (aparece quando entra na viewport) */
        .site [data-reveal] { opacity: 0; transform: translateY(26px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); transition-delay: var(--d, 0ms); }
        .site [data-reveal].is-visible { opacity: 1; transform: none; }

        /* Micro-interações */
        .site a, .site button { transition: color .2s ease, background-color .2s ease, border-color .2s ease, transform .2s ease; }
        .site img { transition: transform .5s cubic-bezier(.16,1,.3,1); }

        @media (prefers-reduced-motion: reduce) {
            .site-reveal, .site [data-reveal] { opacity: 1 !important; transform: none !important; animation: none !important; transition: none !important; }
        }

        /* Azul exato da referência (#4d8dff) — remapeia os tons do Tailwind usados no site. */
        .site .text-blue-300, .site .text-blue-400, .site .text-blue-500 { color: #4d8dff; }
        .site .hover\:text-blue-300:hover, .site .hover\:text-blue-400:hover { color: #6aa0ff; }
        .site .bg-blue-500, .site .bg-blue-600 { background-color: #4d8dff; }
        .site .bg-blue-500\/5 { background-color: rgba(77,141,255,0.05); }
        .site .hover\:bg-blue-500:hover, .site .hover\:bg-blue-600:hover { background-color: #6aa0ff; }
        .site .border-blue-500, .site .border-blue-500\/20 { border-color: rgba(77,141,255,0.2); }
        .site .border-blue-500\/40 { border-color: rgba(77,141,255,0.4); }
        .site .hover\:border-blue-500\/40:hover, .site .hover\:border-blue-500:hover { border-color: rgba(77,141,255,0.5); }
        .site .from-blue-400 { --tw-gradient-from: #4d8dff var(--tw-gradient-from-position); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent); }
        .site .to-blue-600 { --tw-gradient-to: #6aa0ff var(--tw-gradient-to-position); }
    </style>
</head>

<body class="site min-h-dvh bg-[#0a0a0c] text-zinc-100 antialiased">
    <x-site.header :active="$active" />

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    <script>
        (function () {
            const els = document.querySelectorAll('.site [data-reveal]');
            if (!els.length) return;

            // Stagger: atrasa elementos irmãos (ex.: cards de uma grade) em cascata.
            els.forEach((el) => {
                const sibs = Array.from(el.parentElement.children).filter((c) => c.hasAttribute('data-reveal'));
                const i = sibs.indexOf(el);
                if (i > 0) el.style.setProperty('--d', Math.min(i, 6) * 80 + 'ms');
            });

            if (!('IntersectionObserver' in window)) {
                els.forEach((el) => el.classList.add('is-visible'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
            els.forEach((el) => io.observe(el));
        })();
    </script>
</body>
</html>
