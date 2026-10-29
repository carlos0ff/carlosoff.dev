@props(['about'])

@php
    $resumeAvailable = filled($about['resume']) && (
        \Illuminate\Support\Str::startsWith($about['resume'], ['http://', 'https://']) || file_exists(public_path($about['resume']))
    );

    $initials = \Illuminate\Support\Str::of($about['name'])->explode(' ')->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');

    $resumeClasses = 'flex flex-1 items-center justify-center gap-2 rounded-md border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white px-4 py-3 text-sm font-semibold';
    $iconButtonClasses = 'grid h-12 w-12 shrink-0 place-items-center rounded-md border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white text-primary-color';
@endphp

<section id="about" {{ $attributes->class('grid grid-cols-1 items-start gap-x-16 gap-y-10 lg:grid-cols-[minmax(0,1fr)_400px]') }}>
    <div class="experience-reveal min-w-0">
        <h1 class="font-incognito text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl">{{ $about['headline'] }}</h1>

        @if (! empty($about['subtitle']))
            <p class="mt-3 text-lg font-medium dark:text-zinc-300 text-zinc-700">{{ $about['subtitle'] }}</p>
        @endif

        @if (! empty($about['location']) || ! empty($about['professional_status']))
            <ul class="mt-5 flex flex-wrap items-center gap-2 text-sm" aria-label="Localização e status profissional">
                @if (! empty($about['location']))
                    <li data-about-location class="rounded-full border dark:border-zinc-600 border-zinc-300 dark:bg-white/5 bg-zinc-50 px-3 py-1 dark:text-zinc-300 text-zinc-600">{{ $about['location'] }}</li>
                @endif
                @if (! empty($about['professional_status']))
                    <li data-about-status class="rounded-full border border-primary-color/50 px-3 py-1 text-primary-color">{{ $about['professional_status'] }}</li>
                @endif
            </ul>
        @endif

        <div class="mt-8 space-y-5 text-base leading-relaxed dark:text-zinc-400 text-zinc-600">
            @if (! empty($about['description']))
                <p class="text-lg dark:text-zinc-300 text-zinc-700">{{ $about['description'] }}</p>
            @endif

            @foreach ($about['paragraphs'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        @if (! empty($about['links']))
            <ul class="mt-6 flex flex-wrap items-center gap-3 text-sm font-semibold" aria-label="Links">
                @foreach ($about['links'] as $link)
                    <li>
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border dark:border-zinc-800 border-zinc-200 px-4 py-2 transition-colors hover:border-primary-color hover:text-primary-color">{{ $link['label'] }} <span aria-hidden="true">↗</span></a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($about['quote'])
            <blockquote class="relative overflow-hidden tracking-tight text-lg my-8 lg:py-6 lg:pl-6 pr-12 p-4 border dark:border-zinc-800 border-zinc-200 rounded-md">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" class="text-7xl absolute -top-7 -right-5 -rotate-12 dark:text-zinc-800 text-zinc-200" aria-hidden="true" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.309 17.708C22.196 15.66 22.006 13.03 22 13V5a1 1 0 0 0-1-1h-6c-1.103 0-2 .897-2 2v7a1 1 0 0 0 1 1h3.078a2.89 2.89 0 0 1-.429 1.396c-.508.801-1.465 1.348-2.846 1.624l-.803.16V20h1c2.783 0 4.906-.771 6.309-2.292zm-11.007 0C11.19 15.66 10.999 13.03 10.993 13V5a1 1 0 0 0-1-1h-6c-1.103 0-2 .897-2 2v7a1 1 0 0 0 1 1h3.078a2.89 2.89 0 0 1-.429 1.396c-.508.801-1.465 1.348-2.846 1.624l-.803.16V20h1c2.783 0 4.906-.771 6.309-2.292z"></path>
                </svg>
                {{ $about['quote'] }}
            </blockquote>
        @endif
    </div>

    {{-- Foto, currículo e e-mail --}}
    <aside class="experience-reveal w-full max-w-[400px] min-w-0" style="--reveal-index: 2">
        <div class="aspect-square w-full overflow-hidden rounded-2xl border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white">
            @if ($about['photo'])
                <img src="{{ asset($about['photo']) }}" alt="Foto de {{ $about['name'] }}" width="400" height="400" class="h-full w-full object-cover">
            @else
                {{-- Sem foto: iniciais em destaque --}}
                <div data-photo-fallback class="grid h-full w-full place-items-center text-7xl font-bold tracking-tight dark:text-zinc-600 text-zinc-300" aria-hidden="true">{{ $initials }}</div>
            @endif
        </div>

        <div class="mt-4 flex items-center gap-3">
            @if ($resumeAvailable)
                <a href="{{ asset($about['resume']) }}" target="_blank" rel="noopener" class="{{ $resumeClasses }} transition-colors hover:border-primary-color/60">
                    Ver currículo
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                </a>
                <a href="{{ asset($about['resume']) }}" download aria-label="Baixar currículo" class="{{ $iconButtonClasses }} transition-colors hover:border-primary-color/60">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" x2="12" y1="15" y2="3"></line></svg>
                </a>
            @else
                <span data-resume-disabled aria-disabled="true" title="Currículo ainda não disponível" class="{{ $resumeClasses }} cursor-not-allowed opacity-50">
                    Ver currículo
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                </span>
                <span aria-hidden="true" class="{{ $iconButtonClasses }} cursor-not-allowed opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" x2="12" y1="15" y2="3"></line></svg>
                </span>
            @endif
        </div>

        @if ($about['email'])
            <a href="mailto:{{ $about['email'] }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:text-primary-color">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                {{ $about['email'] }}
            </a>
        @endif
    </aside>
</section>
