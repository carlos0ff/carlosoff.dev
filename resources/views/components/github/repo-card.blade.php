@props(['repo'])
<a href="{{ $repo['url'] }}" rel="noopener" target="_blank" class="flex flex-col gap-3 dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md p-5 hover:border-primary-color dark:hover:border-primary-color transition-colors" >
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-lg font-semibold truncate">{{ $repo['name'] }}</h3>
        <span class="flex items-center gap-1 text-xs text-zinc-500 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L0 9.306l8.332-1.151z" />
            </svg>
            {{ $repo['stars'] }}
        </span>
    </div>

    @if($repo['description'])
        <p class="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{{ $repo['description'] }}</p>
    @endif

    @if($repo['language'])
        <div class="flex items-center gap-2 text-xs text-zinc-500 mt-auto">
            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $repo['languageColor'] ?? '#8b949e' }}"></span>
            {{ $repo['language'] }}
        </div>
    @endif
</a>
