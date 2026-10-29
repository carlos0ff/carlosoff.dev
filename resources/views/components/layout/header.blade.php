<header class="text-sm py-6 border-b dark:border-zinc-800 border-zinc-200 z-30 md:mb-28 mb-10">
<div class="max-w-6xl mx-auto md:px-16 px-6 flex md:grid md:grid-cols-3 items-center justify-between md:justify-normal">

    {{-- LOGO --}}
    <div class="flex justify-start">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-base font-bold text-white hover:text-primary-color transition-colors">
            <img src="{{ asset('animated/laravel-animated.svg') }}" class="w-8 h-7 animate-pulse" />
            <span>Jose Carlos</span>
        </a>
    </div>

    <nav class="hidden md:flex justify-center">
        <ul class="flex items-center gap-4 lg:gap-6">
            <li>
                <x-navigation.nav-link :href="route('home')" :active="request()->routeIs('home')">Início</x-navigation.nav-link>
            </li>
            <li>
                <x-navigation.nav-link :href="route('about')" :active="request()->routeIs('about')">Sobre</x-navigation.nav-link>
            </li>
            <li>
                <x-navigation.nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">Projetos</x-navigation.nav-link>
            </li>
            <!-- <li>
                <x-navigation.nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">Blog</x-navigation.nav-link>
            </li> -->
        </ul>
    </nav>

    <div class="flex items-center justify-end gap-x-3">
        <x-layout.language-selector />

        <button aria-label="Toggle Menu" class="md:hidden dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md p-2">
            <svg stroke="currentColor" fill="none" stroke-width="0" viewBox="0 0 15 15" class="text-xl" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.5 3C1.22386 3 1 3.22386 1 3.5C1 3.77614 1.22386 4 1.5 4H13.5C13.7761 4 14 3.77614 14 3.5C14 3.22386 13.7761 3 13.5 3H1.5ZM1 7.5C1 7.22386 1.22386 7 1.5 7H13.5C13.7761 7 14 7.22386 14 7.5C14 7.77614 13.7761 8 13.5 8H1.5C1.22386 8 1 7.77614 1 7.5ZM1 11.5C1 11.2239 1.22386 11 1.5 11H13.5C13.7761 11 14 11.2239 14 11.5C14 11.7761 13.7761 12 13.5 12H1.5C1.22386 12 1 11.7761 1 11.5Z" fill="currentColor"></path>
            </svg>
        </button>

        <x-navigation.mobile-menu />
    </div>
</div>
</header>
