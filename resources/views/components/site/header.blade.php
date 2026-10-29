@props(['active' => 'home'])

@php
    $nav = [
        ['label' => 'Início', 'route' => 'home', 'key' => 'home'],
        ['label' => 'Sobre', 'route' => 'about', 'key' => 'about'],
        ['label' => 'Projetos', 'route' => 'projects.index', 'key' => 'projects'],
        ['label' => 'Contato', 'route' => 'contact', 'key' => 'contact'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-white/5 bg-[#0a0a0c]/80 backdrop-blur-md">
    <div class="mx-auto flex max-w-[1240px] items-center justify-between gap-4 px-6 py-4 sm:px-14">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="grid h-7 w-7 place-items-center rounded-md bg-blue-600 text-sm font-bold text-white font-display">C</span>
            <span class="font-display text-sm font-semibold tracking-tight text-white">José Carlos</span>
        </a>

        {{-- Nav --}}
        <nav class="hidden md:block">
            <ul class="flex items-center gap-7 font-mono2 text-xs">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="transition-colors {{ $active === $item['key'] ? 'text-blue-400' : 'text-zinc-400 hover:text-white' }}">{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- CTA --}}
        <a href="{{ route('contact') }}"
           class="inline-flex items-center gap-1.5 rounded-md border border-blue-500/40 bg-blue-500/5 px-4 py-2 font-mono2 text-xs font-semibold text-blue-300 transition-colors hover:border-blue-500 hover:bg-blue-600 hover:text-white">
            Vamos conversar <span aria-hidden="true">→</span>
        </a>
    </div>
</header>
