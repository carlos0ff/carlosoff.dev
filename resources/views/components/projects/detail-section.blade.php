@props(['title', 'index' => 0])

<section style="--reveal-index: {{ $index }}" aria-labelledby="{{ \Illuminate\Support\Str::slug($title) }}-heading"
    {{ $attributes->class('experience-reveal') }}
>
    <h2 id="{{ \Illuminate\Support\Str::slug($title) }}-heading" class="font-incognito mb-5 text-2xl font-bold tracking-tight">{{ $title }}</h2>

    {{ $slot }}
</section>
