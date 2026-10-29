@props(['href', 'active' => false])
<a
    href="{{ $href }}"
    @class([
        'relative text-xs font-bold uppercase transition-colors after:absolute after:left-0 after:-bottom-1 after:h-px after:bg-white dark:after:bg-primary-color after:transition-all after:duration-300 whitespace-nowrap',
        'dark:text-primary-color text-white after:w-full' => $active,
        'dark:text-white text-zinc-600 dark:hover:text-primary-color hover:text-white after:w-0 hover:after:w-full' => ! $active,
    ])
>
    {{ $slot }}
</a>
