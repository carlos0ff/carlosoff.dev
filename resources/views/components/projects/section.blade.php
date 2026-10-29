@props([
    'projects',
    'title' => 'Projetos',
])

<section id="projects" {{ $attributes }}>
    <div class="mb-16">
        <h2 class="font-incognito text-4xl mb-4 font-bold tracking-tight">{{ $title }}</h2>
        <p class="max-w-2xl text-base dark:text-zinc-400 text-zinc-600 leading-relaxed">
            Aqui reúno os projetos que desenvolvi e os experimentos técnicos que continuo evoluindo ao longo da minha jornada como desenvolvedor.
        </p>
    </div>

    @if ($projects->isNotEmpty())
        <div class="space-y-8">
            @foreach ($projects as $project)
                <x-projects.card :project="$project" :index="$loop->index" />
            @endforeach
        </div>

        @if ($projects instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <x-projects.pagination :paginator="$projects" />
        @endif
    @else
        <div data-projects-empty class="dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-xl p-10 text-center dark:text-zinc-400 text-zinc-600">
            Nenhum projeto publicado por enquanto. Volte em breve!
        </div>
    @endif
</section>
