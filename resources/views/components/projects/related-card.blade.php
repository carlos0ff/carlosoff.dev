@props(['project'])

@php
    $hasDetails = $project->hasDetailPage();
@endphp

<article class="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white transition-colors duration-300 hover:border-primary-color/60" >
    <div class="relative aspect-video w-full overflow-hidden">
        <x-projects.cover :project="$project" class="transition-transform duration-500 group-hover:scale-[1.03]" />
    </div>

    <div class="flex min-w-0 flex-1 flex-col p-6">
        <h3 class="font-incognito text-xl font-semibold tracking-tight sm:text-2xl">
            @if ($hasDetails)
                {{-- Link "esticado": o card inteiro leva à página do projeto; links externos ficam acima (z-10). --}}
                <a href="{{ route('projects.show', $project) }}" class="transition-colors hover:text-primary-color after:absolute after:inset-0 after:content-['']">{{ $project->title }}</a>
            @else
                {{ $project->title }}
            @endif
        </h3>

        <p class="mt-3 line-clamp-3 text-[15px] leading-relaxed dark:text-zinc-400 text-zinc-600">
            {{ $project->description }}
        </p>

        @if ($hasDetails || $project->demo_url)
            <div class="mt-auto flex flex-wrap items-center gap-x-5 gap-y-2 pt-5 text-sm font-semibold">
                @if ($hasDetails)
                    <span class="inline-flex items-center gap-1 text-primary-color">Ver detalhes <span aria-hidden="true">→</span></span>
                @endif

                @if ($project->demo_url)
                    <a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="relative z-10 inline-flex items-center gap-1 text-primary-color transition-colors hover:text-primary-color/80">Demo <span aria-hidden="true">↗</span></a>
                @endif
            </div>
        @endif
    </div>
</article>
