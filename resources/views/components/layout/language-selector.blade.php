{{-- IDs preservados de propósito: resources/js/app.js (initLangSwitcher) depende deles --}}
<div id="lang-switcher" class="relative">
    <button
        id="lang-switcher-btn"
        type="button"
        aria-haspopup="listbox"
        aria-expanded="false"
        class="flex items-center gap-1.5 text-xs font-bold dark:text-white text-zinc-600 dark:hover:text-primary-color hover:text-white uppercase transition-colors dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md px-3 py-2"
    >
        <span id="lang-switcher-label">PT</span>
        <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" class="w-3 h-3 transition-transform" id="lang-switcher-chevron" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <ul
        id="lang-switcher-menu"
        role="listbox"
        class="hidden absolute right-0 mt-2 w-40 dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md shadow-lg overflow-hidden z-40"
    >
        <li>
            <button type="button" data-lang="pt" data-lang-label="PT" class="lang-option w-full text-left px-3 py-2 text-xs font-bold dark:text-white text-zinc-600 dark:hover:text-primary-color hover:text-white transition-colors">
                PT — Português
            </button>
        </li>
        <li>
            <button type="button" data-lang="en" data-lang-label="EN" class="lang-option w-full text-left px-3 py-2 text-xs font-bold dark:text-white text-zinc-600 dark:hover:text-primary-color hover:text-white transition-colors">
                EN — English
            </button>
        </li>
    </ul>
</div>
