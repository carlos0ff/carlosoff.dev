<x-layouts.site title="Projetos | José Carlos" description="Projetos e estudos de caso — do conceito ao deploy." active="projects">
    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-20 pb-8">
        <p class="font-mono2 text-xs tracking-wider text-blue-400">/ PROJETOS</p>
        <h1 class="mt-4 font-display text-4xl font-bold tracking-tight text-white sm:text-5xl">Estudos de caso</h1>
        <p class="mt-4 max-w-2xl text-lg leading-relaxed text-zinc-400">
            Projetos que desenvolvi e experimentos técnicos que continuo evoluindo — do conceito ao deploy.
        </p>
    </section>

    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pb-8">
        @if ($projects->isEmpty())
            <div class="rounded-xl border border-white/10 bg-white/[0.02] p-10 text-center text-zinc-400">
                Nenhum projeto publicado por enquanto. Volte em breve!
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    @php $hasDetails = $project->hasDetailPage(); $href = $hasDetails ? route('projects.show', $project) : ($project->demo_url ?: ($project->github_url ?: '#')); @endphp
                    <article data-reveal class="group flex flex-col overflow-hidden rounded-xl border border-white/10 bg-white/[0.02] transition-colors hover:border-white/20">
                        <a href="{{ $href }}" @if (! $hasDetails) target="_blank" rel="noopener" @endif class="block overflow-hidden">
                            <div class="aspect-[16/10] w-full overflow-hidden">
                                @if ($project->image_url)
                                    <img src="{{ $project->image_url }}" alt="Prévia do projeto {{ $project->title }}" loading="lazy" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]">
                                @else
                                    <div class="flex h-full w-full items-center justify-center" style="background-image: repeating-linear-gradient(135deg, #18181b 0 10px, #141417 10px 20px);"><span class="font-mono2 text-xs text-zinc-600">[ {{ $project->title }} ]</span></div>
                                @endif
                            </div>
                        </a>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex items-center justify-between gap-2">
                                @if (! empty($project->technologies))<p class="font-mono2 text-xs text-blue-400">{{ collect($project->technologies)->take(2)->implode(' · ') }}</p>@endif
                                @if ($project->featured)<span data-project-featured class="font-mono2 text-[10px] uppercase tracking-wider text-amber-400">★ destaque</span>@endif
                            </div>
                            <h2 class="mt-2 font-display text-xl font-bold tracking-tight text-white">
                                <a href="{{ $href }}" @if (! $hasDetails) target="_blank" rel="noopener" @endif class="transition-colors hover:text-blue-300">{{ $project->title }}</a>
                            </h2>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-400">{{ \Illuminate\Support\Str::limit($project->description, 110) }}</p>
                            <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 font-mono2 text-sm font-semibold">
                                @if ($hasDetails)<a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300">Ver estudo de caso <span aria-hidden="true">→</span></a>@endif
                                @if ($project->demo_url)<a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-zinc-400 hover:text-white">Demo <span aria-hidden="true">↗</span></a>@endif
                                @if ($project->github_url)<a href="{{ $project->github_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-zinc-400 hover:text-white">GitHub <span aria-hidden="true">↗</span></a>@endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($projects->hasPages())
                <nav class="mt-12 flex items-center justify-between gap-4 border-t border-white/5 pt-6 font-mono2 text-sm" aria-label="Paginação">
                    @if ($projects->onFirstPage())
                        <span class="text-zinc-700">← anterior</span>
                    @else
                        <a href="{{ $projects->previousPageUrl() }}" class="text-zinc-400 transition-colors hover:text-white">← anterior</a>
                    @endif
                    <span class="text-zinc-500">página {{ $projects->currentPage() }} de {{ $projects->lastPage() }}</span>
                    @if ($projects->hasMorePages())
                        <a href="{{ $projects->nextPageUrl() }}" class="text-zinc-400 transition-colors hover:text-white">próxima →</a>
                    @else
                        <span class="text-zinc-700">próxima →</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
</x-layouts.site>
