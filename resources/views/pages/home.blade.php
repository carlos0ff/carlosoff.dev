@php
    $bio = $about['paragraphs'][0] ?? null;
    $stackByCategory = collect($stack)->groupBy('category');

    // Itens da faixa (marquee) sob a hero — editável aqui. 'icon' = arquivo em public/image/stack.
    $marqueeItems = [
        ['name' => 'Laravel', 'icon' => 'laravel'],
        ['name' => 'PHP', 'icon' => 'php'],
        ['name' => 'Java', 'icon' => 'java'],
        ['name' => 'Spring Boot', 'icon' => 'spring'],
        ['name' => 'Docker', 'icon' => 'docker'],
        ['name' => 'MySQL', 'icon' => 'mysql'],
        ['name' => 'JavaScript', 'icon' => 'javascript'],
        ['name' => 'Tailwind', 'icon' => 'tailwindcss'],
        ['name' => 'Bootstrap', 'icon' => 'bootstrap'],
        ['name' => 'GitHub', 'icon' => 'github'],
    ];
@endphp

<x-layouts.site title="José Carlos — Desenvolvedor Backend PHP & Laravel"
    description="Desenvolvedor backend em PHP e Laravel. Construo aplicações web do conceito ao deploy, com código que se explica."
    active="home"
>
    {{-- ═══ HERO ═══ --}}
    <section class="nhero mx-auto max-w-[1440px] px-6 pt-20 pb-24 sm:px-14 sm:pt-24">
        {{-- Campo de estrelas atrás de toda a hero (decorativo) --}}
        <div class="nhero-stars" aria-hidden="true">
            @php
                $heroStars = [];
                for ($i = 0; $i < 90; $i++) {
                    $h = crc32('hero'.$i);
                    $heroStars[] = [
                        'x' => round(($h % 1000) / 10, 1),
                        'y' => round((intdiv($h, 1000) % 1000) / 10, 1),
                        'sz' => round(1 + (($h >> 3) % 20) / 10, 1),
                        'tw' => round(2.5 + (($h >> 7) % 45) / 10, 1),
                        'dl' => round((($h >> 11) % 60) / 10, 1),
                        'cross' => $i % 18 === 5,
                    ];
                }
            @endphp
            @foreach ($heroStars as $s)
                <span class="nhero-star{{ $s['cross'] ? ' nhero-star--cross' : '' }}"
                      style="left:{{ $s['x'] }}%;top:{{ $s['y'] }}%;--sz:{{ $s['sz'] }}px;--tw:{{ $s['tw'] }}s;--dl:{{ $s['dl'] }}s"></span>
            @endforeach
            <span class="nhero-shooting"></span>
        </div>

        <div class="grid items-center gap-12 md:grid-cols-2 md:gap-10">
            {{-- Coluna de texto --}}
            <div class="max-w-[560px]">
                {{-- Selo com pontinho no acento --}}
                <div class="hero-enter inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3.5 py-1.5 font-mono2 text-xs text-zinc-300" style="--he:1">
                    <span class="h-[7px] w-[7px] rounded-full bg-blue-500"></span>
                    Disponível para projetos
                </div>

                {{-- Título: Fraunces, com o destaque em itálico + acento --}}
                <h1 class="hero-enter font-fraunces mt-7 text-[clamp(2.6rem,6vw,4.6rem)] font-medium leading-[1.03] tracking-[-0.03em] text-white" style="--he:2">
                    Backend em Laravel que <em class="italic text-blue-400">continua de pé</em> depois do deploy.
                </h1>

                {{-- Subtítulo --}}
                <p class="hero-enter mt-7 max-w-[480px] text-lg leading-relaxed text-zinc-400" style="--he:3">
                    Sou o José Carlos. Pego a ideia, desenho a API e o banco, e coloco no ar com Docker. Código limpo o bastante para você entender sem precisar de mim.
                </p>

                {{-- Ações --}}
                <div class="hero-enter mt-9 flex flex-wrap items-center gap-6" style="--he:4">
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3.5 text-[15px] font-semibold text-white transition-transform duration-200 hover:-translate-y-0.5">Conversar sobre o projeto</a>
                    <a href="{{ route('projects.index') }}" class="border-b border-white/25 pb-0.5 font-mono2 text-sm text-zinc-400 transition-colors hover:border-[#4d8dff] hover:text-zinc-100">Ver o que já construí</a>
                </div>
            </div>

            {{-- Órbita cósmica (decorativa) --}}
            <div class="hero-enter" style="--he:5" aria-hidden="true">
                @php
                    // Estrelas com posições/tempos fixos (determinísticos, sem afetar o RNG global).
                    $stars = [];
                    for ($i = 0; $i < 46; $i++) {
                        $h = crc32('star'.$i);
                        $stars[] = [
                            'x' => round(($h % 1000) / 10, 1),
                            'y' => round((intdiv($h, 1000) % 1000) / 10, 1),
                            'sz' => round(1 + (($h >> 3) % 20) / 10, 1),
                            'tw' => round(2 + (($h >> 7) % 40) / 10, 1),
                            'dl' => round((($h >> 11) % 50) / 10, 1),
                            'cross' => $i % 14 === 3,
                        ];
                    }
                @endphp
                <div class="orbit" id="heroOrbit">
                    {{-- Campo de estrelas --}}
                    <div class="orbit-stars">
                        @foreach ($stars as $s)
                            <span class="orbit-star{{ $s['cross'] ? ' orbit-star--cross' : '' }}"
                                  style="left:{{ $s['x'] }}%;top:{{ $s['y'] }}%;--sz:{{ $s['sz'] }}px;--tw:{{ $s['tw'] }}s;--dl:{{ $s['dl'] }}s"></span>
                        @endforeach
                        <span class="orbit-shooting"></span>
                    </div>

                    {{-- Glow radial de fundo --}}
                    <div class="orbit-glow"></div>

                    {{-- Anéis + planetas (o planeta gira junto com o anel) --}}
                    <div class="orbit-ring orbit-ring--1"><span class="orbit-planet orbit-planet--accent"></span></div>
                    <div class="orbit-ring orbit-ring--2">
                        <div class="orbit-trail"></div>
                        <span class="orbit-planet orbit-planet--white"></span>
                    </div>
                    <div class="orbit-ring orbit-ring--3"><span class="orbit-planet orbit-planet--accent"></span><span class="orbit-planet orbit-planet--white-2"></span></div>
                    <div class="orbit-ring orbit-ring--4"><span class="orbit-planet orbit-planet--accent-2"></span></div>

                    {{-- Onda (ripple) + centro --}}
                    <div class="orbit-ripple"></div>
                    <div class="orbit-center">PHP</div>
                </div>
            </div>
        </div>

        {{-- CSS exclusivo da hero: Fraunces, palco da órbita e entradas --}}
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&display=swap');

            .font-fraunces { font-family: 'Fraunces', Georgia, 'Times New Roman', serif; }

            /* Acento terracota, exclusivo da hero (o resto do site segue azul). */
            .nhero .bg-blue-500 { background-color: #e8794f; }
            .nhero .text-blue-400 { color: #e8794f; }
            .nhero .bg-blue-600 { background-color: #e8794f; color: #14120f; }
            .nhero .hover\:border-\[\#4d8dff\]:hover { border-color: #e8794f; }

            /* Tipografia da hero: render mais nítido + título maior e mais ajustado. */
            .nhero { position: relative; overflow: hidden;
                -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }
            .nhero > .grid { position: relative; z-index: 1; }
            .nhero h1 { font-size: clamp(2.9rem, 6.5vw, 5.4rem); line-height: 1.02; letter-spacing: -0.038em; font-optical-sizing: auto; }
            .nhero h1 em { font-optical-sizing: auto; }
            .nhero p { text-wrap: pretty; }

            /* Campo de estrelas de toda a hero (atrás do conteúdo) */
            .nhero-stars { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
            .nhero-star {
                position: absolute; width: var(--sz); height: var(--sz);
                margin: calc(var(--sz) / -2) 0 0 calc(var(--sz) / -2);
                border-radius: 50%; background: #fff; opacity: .18;
                animation: nhero-twinkle var(--tw) ease-in-out infinite; animation-delay: var(--dl);
            }
            .nhero-star--cross::before, .nhero-star--cross::after {
                content: ''; position: absolute; left: 50%; top: 50%;
                background: rgba(255, 255, 255, 0.5); transform: translate(-50%, -50%);
            }
            .nhero-star--cross::before { width: 1px; height: 320%; }
            .nhero-star--cross::after  { width: 320%; height: 1px; }
            @keyframes nhero-twinkle { 0%, 100% { opacity: .08; } 50% { opacity: .55; } }

            .nhero-shooting {
                position: absolute; top: 13%; left: 4%; width: 120px; height: 1px; opacity: 0;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.85));
                animation: nhero-shoot 9s linear infinite;
            }
            @keyframes nhero-shoot {
                0% { transform: translate(0, 0) rotate(16deg); opacity: 0; }
                3% { opacity: .85; }
                12% { transform: translate(760px, 215px) rotate(16deg); opacity: 0; }
                100% { transform: translate(760px, 215px) rotate(16deg); opacity: 0; }
            }

            /* Entrada em sequência: fade + translateY(16px), 800ms, 100ms entre cada */
            .site .hero-enter { opacity: 0; animation: hero-in 800ms cubic-bezier(0.22, 1, 0.36, 1) forwards; animation-delay: calc(var(--he, 0) * 100ms); }
            @keyframes hero-in { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

            /* ── Órbita cósmica ───────────────────────────────────────────────
               Wrapper quadrado responsivo; tudo em % para escalar junto e aparecer
               completo (sem corte) de 1440px a 375px. Parallax leve via --rx/--ry. */
            .orbit {
                position: relative;
                width: min(100%, 720px);
                aspect-ratio: 1 / 1;
                margin-inline: auto;
                overflow: visible;
                --accent: #e8794f;
                transform: perspective(900px) rotateX(var(--ry, 0deg)) rotateY(var(--rx, 0deg));
                transition: transform .25s ease-out;
            }

            /* Glow radial de fundo (profundidade) */
            .orbit-glow {
                position: absolute; inset: 14%; border-radius: 50%; pointer-events: none;
                background: radial-gradient(circle, rgba(232, 121, 79, 0.10), transparent 62%);
            }

            /* Estrelas (recortadas na área da órbita p/ a estrela cadente não gerar scroll) */
            .orbit-stars { position: absolute; inset: 0; overflow: hidden; }
            .orbit-star {
                position: absolute; width: var(--sz); height: var(--sz);
                margin: calc(var(--sz) / -2) 0 0 calc(var(--sz) / -2);
                border-radius: 50%; background: #fff; opacity: .3;
                animation: orbit-twinkle var(--tw) ease-in-out infinite; animation-delay: var(--dl);
            }
            .orbit-star--cross::before, .orbit-star--cross::after {
                content: ''; position: absolute; left: 50%; top: 50%;
                background: rgba(255, 255, 255, 0.65); transform: translate(-50%, -50%);
            }
            .orbit-star--cross::before { width: 1px; height: 340%; }
            .orbit-star--cross::after  { width: 340%; height: 1px; }
            @keyframes orbit-twinkle { 0%, 100% { opacity: .15; } 50% { opacity: .95; } }

            /* Estrela cadente (cruza de tempos em tempos) */
            .orbit-shooting {
                position: absolute; top: 10%; left: 2%; width: 90px; height: 1px; opacity: 0;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.85));
                animation: orbit-shoot 11s linear infinite;
            }
            @keyframes orbit-shoot {
                0% { transform: translate(0, 0) rotate(20deg); opacity: 0; }
                3% { opacity: .9; }
                13% { transform: translate(520px, 190px) rotate(20deg); opacity: 0; }
                100% { transform: translate(520px, 190px) rotate(20deg); opacity: 0; }
            }

            /* Anéis concêntricos (raios crescentes, sentidos/velocidades diferentes) */
            .orbit-ring {
                position: absolute; border-radius: 50%; will-change: transform;
                border: 1px solid rgba(255, 255, 255, 0.10);
            }
            .orbit-ring--1 { inset: 31%; animation: orbit-spin 40s linear infinite; }
            .orbit-ring--2 { inset: 21%; animation: orbit-spin 60s linear infinite reverse; }
            .orbit-ring--3 { inset: 10%; animation: orbit-spin 90s linear infinite; }
            .orbit-ring--4 { inset: 0;   animation: orbit-spin 75s linear infinite reverse; }
            @keyframes orbit-spin { to { transform: rotate(360deg); } }

            /* Planetas luminosos (posicionados na borda do anel; giram junto) */
            .orbit-planet {
                position: absolute; top: 0; left: 50%; width: 9px; height: 9px;
                margin: -4.5px 0 0 -4.5px; border-radius: 50%;
            }
            .orbit-planet--accent   { background: var(--accent); box-shadow: 0 0 12px var(--accent); }
            .orbit-planet--accent-2 { background: var(--accent); box-shadow: 0 0 12px var(--accent); top: 50%; left: 100%; }
            .orbit-planet--white    { background: #e8e8f0; box-shadow: 0 0 8px rgba(255, 255, 255, 0.6); width: 7px; height: 7px; margin: -3.5px 0 0 -3.5px; }
            .orbit-planet--white-2  { background: #cfd0dc; box-shadow: 0 0 7px rgba(255, 255, 255, 0.45); width: 6px; height: 6px; margin: -3px 0 0 -3px; top: 100%; left: 50%; }

            /* Rastro (cauda em conic-gradient, recortada na borda do anel) */
            .orbit-trail {
                position: absolute; inset: 0; border-radius: 50%; pointer-events: none;
                background: conic-gradient(from -18deg, transparent 0 320deg, rgba(232, 121, 79, 0.35) 352deg, rgba(232, 121, 79, 0) 360deg);
                -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 2px));
                        mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 2px));
            }

            /* Centro "PHP": halo pulsando devagar */
            .orbit-center {
                position: absolute; top: 50%; left: 50%; width: 24%; aspect-ratio: 1 / 1;
                display: grid; place-items: center; border-radius: 50%;
                background: var(--site-bg); border: 1px solid rgba(232, 121, 79, 0.6);
                font-family: 'Fraunces', Georgia, serif; font-style: italic;
                font-size: clamp(1.3rem, 3.8vw, 2.4rem); color: var(--accent);
                transform: translate(-50%, -50%); animation: orbit-pulse 4s ease-in-out infinite;
            }
            @keyframes orbit-pulse {
                0%, 100% { box-shadow: 0 0 24px rgba(232, 121, 79, 0.22); transform: translate(-50%, -50%) scale(1); }
                50%      { box-shadow: 0 0 42px rgba(232, 121, 79, 0.45); transform: translate(-50%, -50%) scale(1.04); }
            }

            /* Onda (ripple) que expande e desvanece a partir do centro */
            .orbit-ripple {
                position: absolute; top: 50%; left: 50%; width: 24%; aspect-ratio: 1 / 1;
                border-radius: 50%; border: 1px solid rgba(232, 121, 79, 0.5); pointer-events: none;
                transform: translate(-50%, -50%) scale(1); animation: orbit-wave 5s ease-out infinite;
            }
            @keyframes orbit-wave {
                0%   { transform: translate(-50%, -50%) scale(1);   opacity: .6; }
                100% { transform: translate(-50%, -50%) scale(2.4); opacity: 0; }
            }

            /* Menos movimento: tudo estático */
            @media (prefers-reduced-motion: reduce) {
                .site .hero-enter { opacity: 1 !important; transform: none !important; animation: none !important; }
                .orbit { transform: none; }
                .orbit-ring, .orbit-star, .orbit-shooting, .orbit-center, .orbit-ripple,
                .nhero-star, .nhero-shooting { animation: none !important; }
                .orbit-shooting, .orbit-ripple, .nhero-shooting { opacity: 0; }
                .orbit-star { opacity: .5; }
                .nhero-star { opacity: .3; }
            }
        </style>

        {{-- Parallax suave da órbita seguindo o mouse (±6°). Desativado no mobile/reduced-motion. --}}
        <script>
            (function () {
                var orbit = document.getElementById('heroOrbit');
                if (!orbit || !window.matchMedia) { return; }
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
                    window.matchMedia('(pointer: coarse)').matches) { return; }

                var MAX = 6;
                window.addEventListener('mousemove', function (e) {
                    var r = orbit.getBoundingClientRect();
                    var dx = (e.clientX - (r.left + r.width / 2)) / (window.innerWidth / 2);
                    var dy = (e.clientY - (r.top + r.height / 2)) / (window.innerHeight / 2);
                    orbit.style.setProperty('--rx', (Math.max(-1, Math.min(1, dx)) * MAX).toFixed(2) + 'deg');
                    orbit.style.setProperty('--ry', (Math.max(-1, Math.min(1, -dy)) * MAX).toFixed(2) + 'deg');
                }, { passive: true });
            })();
        </script>
    </section>

    {{-- ═══ MARQUEE (faixa full-width sob a hero) ═══ --}}
    <section class="marquee" aria-label="Tecnologias que uso">
        <div class="marquee-track">
            @foreach ($marqueeItems as $item)
                <span class="marquee-item">
                    <img src="{{ asset('image/stack/'.$item['icon'].'.svg') }}" alt="{{ $item['name'] }}" loading="lazy" class="marquee-ico">
                    {{ $item['name'] }}
                </span>
            @endforeach
            {{-- Segunda cópia (apenas visual) para o loop sem emenda --}}
            @foreach ($marqueeItems as $item)
                <span class="marquee-item" aria-hidden="true">
                    <img src="{{ asset('image/stack/'.$item['icon'].'.svg') }}" alt="" loading="lazy" class="marquee-ico">
                    {{ $item['name'] }}
                </span>
            @endforeach
        </div>
    </section>

    <style>
        /* Faixa full-width: não usa o container limitado; desvanece nas pontas. */
        .marquee {
            width: 100%;
            overflow: hidden;
            padding-block: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
                    mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
        }
        .marquee-track {
            display: flex;
            width: max-content;
            will-change: transform;
            animation: marquee-scroll 36s linear infinite;
        }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        /* Espaçamento via margin (não gap): mantém o passo de -50% exato, sem "pulo". */
        .marquee-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-right: 56px;
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 13px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            white-space: nowrap;
            color: #a1a1aa; /* cinza secundário */
            transition: color .2s ease;
        }
        .marquee-ico {
            width: 30px;
            height: 30px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
            /* Silhueta cinza uniforme (sem cor), no mesmo tom do texto. */
            filter: brightness(0) invert(0.63);
            opacity: .9;
            transition: filter .2s ease, opacity .2s ease;
        }
        .marquee-item:hover { color: #fff; } /* clareia levemente no hover */
        .marquee-item:hover .marquee-ico { filter: brightness(0) invert(0.95); opacity: 1; }

        @keyframes marquee-scroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .marquee { overflow: visible; -webkit-mask-image: none; mask-image: none; }
            .marquee-track {
                width: auto;
                animation: none;
                flex-wrap: wrap;
                justify-content: center;
            }
            .marquee-item { margin: 4px 20px; }
            .marquee-item[aria-hidden="true"] { display: none; } /* não repetir na versão estática */
        }
    </style>

    {{-- ═══ PROJETOS EM DESTAQUE ═══ --}}
    @if ($featuredProjects->isNotEmpty())
        @php
            $featured = $featuredProjects->first();
            $rest = $featuredProjects->slice(1)->values();
        @endphp
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-32">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ PROJETOS EM DESTAQUE</p>

            {{-- Destaque: sempre no topo, 100% da largura do container (imagem à esquerda, texto à direita) --}}
            @php
                $tech = ! empty($featured->technologies) ? collect($featured->technologies)->take(2)->implode(' · ') : null;
                $desc = \Illuminate\Support\Str::limit($featured->description, 180);
            @endphp
            <article data-reveal class="group mt-12 flex flex-col overflow-hidden rounded-xl border border-white/10 bg-white/[0.02] transition-colors hover:border-white/20 sm:flex-row">
                <a href="{{ route('projects.show', $featured) }}" class="block shrink-0 overflow-hidden sm:w-1/2">
                    <div class="aspect-[16/10] w-full overflow-hidden">
                        @if ($featured->image_url)
                            <img src="{{ $featured->image_url }}" alt="Prévia do projeto {{ $featured->title }}" loading="lazy" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]">
                        @else
                            <div class="flex h-full w-full items-center justify-center" style="background-image: repeating-linear-gradient(135deg, #18181b 0 10px, #141417 10px 20px);">
                                <span class="font-mono2 text-xs text-zinc-600">[ {{ $featured->title }} ]</span>
                            </div>
                        @endif
                    </div>
                </a>
                <div class="flex flex-1 flex-col p-6 sm:justify-center sm:p-8">
                    @if ($tech)
                        <p class="font-mono2 text-xs text-blue-400">{{ $tech }}</p>
                    @endif
                    <h3 class="mt-2 font-display text-3xl font-bold tracking-tight text-white">
                        <a href="{{ route('projects.show', $featured) }}" class="transition-colors hover:text-blue-300">{{ $featured->title }}</a>
                    </h3>
                    <p class="mt-3 leading-relaxed text-zinc-400">{{ $desc }}</p>
                    <a href="{{ route('projects.show', $featured) }}" class="mt-5 inline-flex items-center gap-1.5 font-mono2 text-sm font-semibold text-blue-400 transition-colors hover:text-blue-300">
                        Ver estudo de caso <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>

            {{-- Demais: 2ª linha, 100% da largura, dividida em N colunas iguais (1fr cada); empilha no mobile --}}
            @if ($rest->isNotEmpty())
                <div class="mt-6 grid grid-cols-1 gap-6 md:[grid-template-columns:repeat(var(--cols),minmax(0,1fr))]" style="--cols: {{ $rest->count() }}">
                    @foreach ($rest as $project)
                        @php
                            $tech = ! empty($project->technologies) ? collect($project->technologies)->take(2)->implode(' · ') : null;
                            $desc = \Illuminate\Support\Str::limit($project->description, 110);
                        @endphp
                        <article data-reveal class="group flex h-full flex-col overflow-hidden rounded-xl border border-white/10 bg-white/[0.02] transition-colors hover:border-white/20">
                            <a href="{{ route('projects.show', $project) }}" class="block overflow-hidden">
                                <div class="aspect-[16/10] w-full overflow-hidden">
                                    @if ($project->image_url)
                                        <img src="{{ $project->image_url }}" alt="Prévia do projeto {{ $project->title }}" loading="lazy" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center" style="background-image: repeating-linear-gradient(135deg, #18181b 0 10px, #141417 10px 20px);">
                                            <span class="font-mono2 text-xs text-zinc-600">[ {{ $project->title }} ]</span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-6">
                                @if ($tech)
                                    <p class="font-mono2 text-xs text-blue-400">{{ $tech }}</p>
                                @endif
                                <h3 class="mt-2 font-display text-2xl font-bold tracking-tight text-white">
                                    <a href="{{ route('projects.show', $project) }}" class="transition-colors hover:text-blue-300">{{ $project->title }}</a>
                                </h3>
                                <p class="mt-3 text-sm leading-relaxed text-zinc-400">{{ $desc }}</p>
                                <a href="{{ route('projects.show', $project) }}" class="mt-5 inline-flex items-center gap-1.5 font-mono2 text-sm font-semibold text-blue-400 transition-colors hover:text-blue-300">
                                    Ver estudo de caso <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="mt-12">
                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 font-mono2 text-sm text-zinc-400 transition-colors hover:text-white">
                    Ver todos os projetos <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    @endif

    {{-- ═══ STACK ═══ --}}
    @if ($stackByCategory->isNotEmpty())
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-32">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ STACK</p>
            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stackByCategory as $category => $items)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6">
                        <h3 class="font-display text-lg font-semibold text-white">{{ $category }}</h3>
                        <ul class="mt-4 space-y-2 font-mono2 text-sm text-zinc-400">
                            @foreach ($items as $tech)
                                <li class="flex items-center gap-2">
                                    <span class="text-blue-500">—</span> {{ $tech['name'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ SOBRE ═══ --}}
    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-32">
        <div class="relative overflow-hidden rounded-2xl border border-blue-500/20 p-8 sm:p-12"
             style="background: radial-gradient(120% 120% at 0% 0%, rgba(37,99,235,0.18), transparent 55%), #0d1117;">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ SOBRE</p>
            <h2 class="mt-4 max-w-2xl font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
                Backend que sustenta produtos de verdade
            </h2>
            <div class="mt-5 max-w-2xl space-y-4 leading-relaxed text-zinc-300">
                @foreach (($about['paragraphs'] ?? []) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
            <a href="{{ route('about') }}" class="mt-6 inline-flex items-center gap-1.5 font-mono2 text-sm font-semibold text-blue-400 transition-colors hover:text-blue-300">
                Quem sou eu <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    {{-- ═══ CTA ═══ --}}
    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-32">
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-6 py-24 text-center">
            <h2 class="mx-auto max-w-2xl font-display text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                Vamos tirar seu produto do mockup e botar no ar?
            </h2>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-500">Começar uma conversa</a>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center justify-center rounded-md border border-white/15 px-5 py-3 text-sm font-semibold text-zinc-200 transition-colors hover:border-white/40 hover:text-white">Ver portfólio</a>
            </div>
        </div>
    </section>
</x-layouts.site>
