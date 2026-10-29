@props(['project', 'index' => 0])

@php
    $hasDetails = $project->hasDetailPage();
    $linkClasses = 'relative z-10 inline-flex items-center gap-1 transition-colors hover:text-primary-color';
@endphp

<article
    style="--reveal-index: {{ $index }}"
    class="experience-reveal group relative flex min-w-0 flex-col gap-6 overflow-hidden rounded border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white p-5 transition-colors duration-300 hover:border-primary-color/60 md:flex-row"
>
    {{-- Imagem (360×210 no desktop, largura total no mobile) --}}
    <div class="relative aspect-video w-full shrink-0 overflow-hidden rounded-md md:aspect-auto md:h-[210px] md:w-[360px]">
        <x-projects.cover :project="$project" class="transition-transform duration-500 group-hover:scale-[1.03]" />
    </div>

    {{-- Conteúdo --}}
    <div class="flex min-w-0 flex-1 flex-col">
        @if ($project->featured)
            <p data-project-featured class="mb-2 text-xs font-semibold uppercase tracking-widest text-primary-color"><span aria-hidden="true">★</span> Destaque</p>
        @endif

        <h3 class="font-incognito text-2xl font-semibold tracking-tight">
            @if ($hasDetails)
                {{-- Link "esticado": o card inteiro leva à página do projeto; links externos ficam acima (z-10). --}}
                <a href="{{ route('projects.show', $project) }}" class="transition-colors hover:text-primary-color after:absolute after:inset-0 after:content-['']">{{ $project->title }}</a>
            @else
                {{ $project->title }}
            @endif
        </h3>

        <p class="mt-2 leading-relaxed dark:text-zinc-400 text-zinc-600">{{ $project->description }}</p>

        @if (! empty($project->technologies))
            <ul class="mt-4 flex flex-wrap gap-2" aria-label="Tecnologias">
                @foreach ($project->technologies as $technology)
                    <x-projects.technology>{{ $technology }}</x-projects.technology>
                @endforeach
            </ul>
        @endif

        @if ($hasDetails || $project->github_url || $project->demo_url)
            <div class="mt-auto flex flex-wrap items-center gap-x-6 gap-y-2 pt-5 text-sm font-semibold">
                @if ($hasDetails)
                    <span class="inline-flex items-center gap-1 text-primary-color">Ver detalhes <span aria-hidden="true">→</span></span>
                @endif

                @if ($project->github_url)
                    <a href="{{ $project->github_url }}" target="_blank" rel="noopener noreferrer" class="{{ $linkClasses }}">GitHub <span aria-hidden="true">↗</span></a>
                @endif

                @if ($project->demo_url)
                    <a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="{{ $linkClasses }} text-primary-color">Ver projeto <span aria-hidden="true">→</span></a>
                @endif
            </div>
        @endif
    </div>
</article>
