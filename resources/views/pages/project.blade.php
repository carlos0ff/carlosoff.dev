@php
    $details = $project->details ?? [];
    $overview = $details['overview'] ?? [];
    $features = $details['features'] ?? [];
    $development = $details['development'] ?? [];
    $challenges = $details['challenges'] ?? [];
    $conclusion = $details['conclusion'] ?? null;
    $extraSections = $details['sections'] ?? [];

    $toParagraphs = fn (?string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $text))));
    $objective = $toParagraphs($details['objective'] ?? null);

    $architecture = $toParagraphs($details['architecture'] ?? null);
    $screenshots = collect($details['screenshots'] ?? [])->map(fn (array $s): array => [
        'src' => \Illuminate\Support\Str::startsWith($s['src'], ['http://', 'https://']) ? $s['src'] : asset($s['src']),
        'alt' => $s['alt'] ?? ('Captura de tela do projeto '.$project->title),
        'caption' => $s['caption'] ?? null,
    ])->values();

    $related = $related ?? collect();
    $tech = ! empty($project->technologies) ? collect($project->technologies)->take(3)->implode(' · ') : null;
@endphp

<x-layouts.site :title="($project->seo_title ?: $project->title.' — Projetos').' | José Carlos'"
    :description="$project->seo_description ?: $project->description"
    :image="$project->og_image ? asset($project->og_image) : $project->image_url"
    ogType="article" active="projects"
>
    <article data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-16 pb-8">
        @if (! empty($isPreview))
            <div role="status" data-preview-banner class="mb-8 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                Pré-visualização do painel: {{ $project->is_active ? 'publicado.' : 'rascunho — não aparece no site.' }}
                <a href="{{ route('admin.projects.edit', $project) }}" class="font-semibold underline">Editar</a>
            </div>
        @endif

        {{-- Navegação --}}
        <nav aria-label="Você está em" class="font-mono2 text-xs text-zinc-500">
            <a href="{{ route('projects.index') }}" class="transition-colors hover:text-blue-400">projetos</a>
            <span aria-hidden="true" class="px-1.5 text-zinc-700">/</span>
            <span class="text-zinc-300">{{ $project->title }}</span>
        </nav>

        {{-- Cabeçalho: título à esquerda, ações no canto superior direito --}}
        <header class="mt-8 flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
                @if ($tech)
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">{{ $tech }}</p>
                @endif
                <h1 class="mt-3 font-display text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">{{ $project->title }}</h1>
                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-zinc-400">{{ $project->description }}</p>
            </div>

            @if ($project->demo_url || $project->github_url)
                <div class="flex shrink-0 flex-wrap items-center gap-3 md:pt-1">
                    @if ($project->demo_url)
                        <a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-500">Ver projeto <span aria-hidden="true">↗</span></a>
                    @endif
                    @if ($project->github_url)
                        <a href="{{ $project->github_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-md border border-white/15 px-4 py-2.5 text-sm font-semibold text-zinc-200 transition-colors hover:border-white/40 hover:text-white">GitHub <span aria-hidden="true">↗</span></a>
                    @endif
                </div>
            @endif
        </header>

        {{-- Capa --}}
        <div class="mt-10 aspect-[16/9] w-full overflow-hidden rounded-xl border border-white/10 bg-white/[0.02]">
            @if ($project->image_url)
                <img src="{{ $project->image_url }}" alt="Prévia do projeto {{ $project->title }}" class="h-full w-full object-cover object-top">
            @else
                <div class="flex h-full w-full items-center justify-center" style="background-image: repeating-linear-gradient(135deg, #18181b 0 10px, #141417 10px 20px);">
                    <span class="font-mono2 text-sm text-zinc-600">[ {{ $project->title }} ]</span>
                </div>
            @endif
        </div>

        <div class="mt-16 space-y-16">
            @if ($overview)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ VISÃO GERAL</p>
                    <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-zinc-400">
                        @foreach ($overview as $p) <p>{{ $p }}</p> @endforeach
                    </div>
                </section>
            @endif

            @if ($objective)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ OBJETIVO</p>
                    <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-zinc-400">
                        @foreach ($objective as $p) <p>{{ $p }}</p> @endforeach
                    </div>
                </section>
            @endif

            {{-- Tecnologias --}}
            @if (! empty($project->technologies))
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ TECNOLOGIAS</p>
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($project->technologies as $technology)
                            <li class="rounded-md border border-white/10 bg-white/[0.03] px-3 py-1.5 font-mono2 text-xs text-zinc-300">
                                {{ $technology }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Funcionalidades --}}
            @if ($features)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ FUNCIONALIDADES</p>
                    <ul class="mt-5 space-y-3 text-zinc-400">
                        @foreach ($features as $feature)
                            <li class="flex gap-3 leading-relaxed">
                                <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500"></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($architecture)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ ARQUITETURA</p>
                    <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-zinc-400">
                        @foreach ($architecture as $p) <p>{{ $p }}</p> @endforeach
                    </div>
                </section>
            @endif

            {{-- Seções extras --}}
            @foreach ($extraSections as $extraSection)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ {{ \Illuminate\Support\Str::upper($extraSection['title']) }}</p>
                    <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-zinc-400">
                        @foreach (($extraSection['paragraphs'] ?? []) as $p) <p>{{ $p }}</p> @endforeach
                    </div>
                    @if (! empty($extraSection['items']))
                        <ul class="mt-4 max-w-3xl space-y-2 text-zinc-400">
                            @foreach ($extraSection['items'] as $it)
                                <li class="flex gap-3 leading-relaxed"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500"></span>
                                    <span>{{ $it }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($extraSection['note']))
                        <p class="mt-4 max-w-3xl rounded-lg border border-blue-500/20 bg-blue-500/5 px-4 py-3 text-sm leading-relaxed text-zinc-300">{{ $extraSection['note'] }}</p>
                    @endif
                    @if (! empty($extraSection['flow']['steps']))
                        <div class="mt-6 max-w-3xl space-y-2">
                            @foreach ($extraSection['flow']['steps'] as $step)
                                <div class="flex gap-3 rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ ($step['status'] ?? null) === 'planned' ? 'bg-zinc-600' : 'bg-blue-500' }}"></span>
                                    <div>
                                        <p class="font-semibold text-zinc-200">{{ $step['label'] }}@if (($step['status'] ?? null) === 'planned') <span class="ml-1 font-mono2 text-[10px] text-zinc-500">(planejado)</span>@endif</p>
                                        @if (! empty($step['detail']))<p class="mt-1 text-sm text-zinc-500">{{ $step['detail'] }}</p>@endif
                                    </div>
                                </div>
                            @endforeach
                            @if (! empty($extraSection['flow']['caption']))<p class="font-mono2 text-xs text-zinc-600">{{ $extraSection['flow']['caption'] }}</p>@endif
                        </div>
                    @endif

                    {{-- Imagem da seção (ex.: diagrama de arquitetura) --}}
                    @if (! empty($extraSection['image']))
                        <figure class="mt-6">
                            <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.02]">
                                <img src="{{ \Illuminate\Support\Str::startsWith($extraSection['image'], ['http://', 'https://']) ? $extraSection['image'] : asset($extraSection['image']) }}"
                                     alt="{{ $extraSection['image_alt'] ?? ('Diagrama de arquitetura do projeto '.$project->title) }}"
                                     loading="lazy" class="w-full">
                            </div>
                            @if (! empty($extraSection['image_caption']))
                                <figcaption class="mt-3 text-center text-sm text-zinc-500">{{ $extraSection['image_caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endif
                </section>
            @endforeach

            {{-- Screenshots --}}
            @if ($screenshots->isNotEmpty())
                <section data-reveal data-ss>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ SCREENSHOTS</p>

                    {{-- Slider: um screenshot por vez; clique abre o modal --}}
                    <div class="relative mt-5 overflow-hidden rounded-xl border border-white/10 bg-white/[0.02]">
                        <div class="flex transition-transform duration-500 ease-out" data-ss-track>
                            @foreach ($screenshots as $i => $shot)
                                <figure class="w-full shrink-0">
                                    <button type="button" data-ss-open data-index="{{ $i }}" class="group block w-full cursor-zoom-in">
                                        <span class="relative block">
                                            <img src="{{ $shot['src'] }}" alt="{{ $shot['alt'] }}" loading="lazy" class="aspect-video w-full bg-black/20 object-cover object-top">
                                            <span aria-hidden="true" class="absolute inset-0 grid place-items-center bg-black/0 opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100">
                                                <span class="grid h-11 w-11 place-items-center rounded-full bg-white/90 text-zinc-900">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
                                                </span>
                                            </span>
                                        </span>
                                    </button>
                                    @if ($shot['caption'])<figcaption class="px-4 py-3 text-center text-sm text-zinc-500">{{ $shot['caption'] }}</figcaption>@endif
                                </figure>
                            @endforeach
                        </div>
                        @if ($screenshots->count() > 1)
                            <button type="button" data-ss-prev aria-label="Imagem anterior" class="absolute left-3 top-[calc(50%-1rem)] grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/55 text-white transition hover:bg-black/80"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                            <button type="button" data-ss-next aria-label="Próxima imagem" class="absolute right-3 top-[calc(50%-1rem)] grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/55 text-white transition hover:bg-black/80"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
                        @endif
                    </div>

                    @if ($screenshots->count() > 1)
                        <div class="mt-4 flex items-center justify-center gap-2" data-ss-dots>
                            @foreach ($screenshots as $i => $shot)
                                <button type="button" data-ss-dot data-index="{{ $i }}" aria-label="Ir para a imagem {{ $i + 1 }}" class="h-1.5 w-6 rounded-full transition-colors {{ $i === 0 ? 'bg-blue-500' : 'bg-white/15 hover:bg-white/30' }}"></button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Dados dos slides (para o modal) --}}
                    <script type="application/json" data-ss-data>@json($screenshots, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>

                    {{-- Modal / lightbox --}}
                    <div data-ss-modal class="fixed inset-0 z-[100] hidden items-center justify-center p-4 sm:p-8" role="dialog" aria-modal="true" aria-label="Screenshots do projeto">
                        <div data-ss-backdrop class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
                        <div class="relative z-10 flex max-h-full w-full max-w-5xl flex-col">
                            <div class="mb-3 flex items-center justify-between gap-4 text-sm text-zinc-300">
                                <span data-ss-counter class="font-mono2 text-xs" aria-live="polite"></span>
                                <button type="button" data-ss-close aria-label="Fechar" class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/5 text-white transition hover:bg-white/15"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
                            </div>
                            <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black">
                                <img data-ss-image src="" alt="" class="mx-auto max-h-[72vh] w-full bg-black object-contain transition duration-300 ease-out">
                                <button type="button" data-ss-mprev aria-label="Imagem anterior" class="absolute left-3 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/50 text-white transition hover:bg-black/80"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                                <button type="button" data-ss-mnext aria-label="Próxima imagem" class="absolute right-3 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/50 text-white transition hover:bg-black/80"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
                            </div>
                            <p data-ss-caption class="mt-3 min-h-5 text-center text-sm text-zinc-300"></p>
                        </div>
                    </div>
                </section>

                <script>
                (function () {
                    const root = document.querySelector('[data-ss]');
                    if (!root) return;
                    let slides = [];
                    try { slides = JSON.parse(root.querySelector('[data-ss-data]').textContent); } catch (e) { return; }
                    if (!Array.isArray(slides) || !slides.length) return;

                    const track = root.querySelector('[data-ss-track]');
                    const dots = Array.from(root.querySelectorAll('[data-ss-dot]'));
                    const modal = root.querySelector('[data-ss-modal]');
                    const mImg = modal.querySelector('[data-ss-image]');
                    const mCap = modal.querySelector('[data-ss-caption]');
                    const mCount = modal.querySelector('[data-ss-counter]');
                    document.body.appendChild(modal); // portal: evita problemas de posicionamento

                    let cur = 0, mcur = 0, lastFocus = null;
                    const single = slides.length < 2;

                    function setSlide(n) {
                        cur = (n + slides.length) % slides.length;
                        track.style.transform = 'translateX(-' + (cur * 100) + '%)';
                        dots.forEach((d, i) => { d.classList.toggle('bg-blue-500', i === cur); d.classList.toggle('bg-white/15', i !== cur); });
                    }
                    root.querySelector('[data-ss-next]')?.addEventListener('click', () => setSlide(cur + 1));
                    root.querySelector('[data-ss-prev]')?.addEventListener('click', () => setSlide(cur - 1));
                    dots.forEach((d) => d.addEventListener('click', () => setSlide(Number(d.dataset.index))));

                    function renderModal() {
                        const s = slides[mcur]; if (!s) return;
                        mImg.src = s.src; mImg.alt = s.alt || '';
                        mCap.textContent = s.caption || '';
                        mCount.textContent = (mcur + 1) + ' / ' + slides.length;
                    }
                    function openModal(i) {
                        mcur = Number.isInteger(i) ? i : 0;
                        lastFocus = document.activeElement;
                        modal.classList.remove('hidden'); modal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                        renderModal();
                        modal.querySelector('[data-ss-close]')?.focus();
                        document.addEventListener('keydown', onKey);
                    }
                    function closeModal() {
                        modal.classList.add('hidden'); modal.classList.remove('flex');
                        document.body.style.overflow = '';
                        document.removeEventListener('keydown', onKey);
                        lastFocus && lastFocus.focus && lastFocus.focus();
                    }
                    function mgo(step) { if (single) return; mcur = (mcur + step + slides.length) % slides.length; renderModal(); }
                    function onKey(e) { if (e.key === 'Escape') closeModal(); else if (e.key === 'ArrowRight') mgo(1); else if (e.key === 'ArrowLeft') mgo(-1); }

                    root.querySelectorAll('[data-ss-open]').forEach((b) => b.addEventListener('click', () => openModal(Number(b.dataset.index))));
                    modal.querySelector('[data-ss-mnext]')?.addEventListener('click', () => mgo(1));
                    modal.querySelector('[data-ss-mprev]')?.addEventListener('click', () => mgo(-1));
                    modal.querySelector('[data-ss-close]')?.addEventListener('click', closeModal);
                    modal.querySelector('[data-ss-backdrop]')?.addEventListener('click', closeModal);
                    if (single) { modal.querySelector('[data-ss-mprev]')?.classList.add('hidden'); modal.querySelector('[data-ss-mnext]')?.classList.add('hidden'); }

                    // swipe no slider
                    let sx = null;
                    track.addEventListener('touchstart', (e) => sx = e.changedTouches[0].clientX, { passive: true });
                    track.addEventListener('touchend', (e) => { if (sx === null) return; const d = e.changedTouches[0].clientX - sx; if (Math.abs(d) > 40) setSlide(cur + (d < 0 ? 1 : -1)); sx = null; }, { passive: true });
                })();
                </script>
            @endif

            {{-- Desenvolvimento e desafios --}}
            @if ($development || $challenges)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ DESENVOLVIMENTO E DESAFIOS</p>
                    @if ($development)
                        <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-zinc-400">
                            @foreach ($development as $p) <p>{{ $p }}</p> @endforeach
                        </div>
                    @endif
                    @foreach ($challenges as $challenge)
                        <div class="mt-6 max-w-3xl rounded-xl border border-white/10 bg-white/[0.02] p-6">
                            <h3 class="font-display text-lg font-semibold text-white">{{ $challenge['title'] }}</h3>
                            <p class="mt-3 font-mono2 text-xs uppercase tracking-widest text-zinc-500">Desafio</p>
                            <p class="mt-1 leading-relaxed text-zinc-400">{{ $challenge['problem'] }}</p>
                            <p class="mt-4 font-mono2 text-xs uppercase tracking-widest text-blue-400">Solução</p>
                            <p class="mt-1 leading-relaxed text-zinc-400">{{ $challenge['solution'] }}</p>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Conclusão --}}
            @if ($conclusion)
                <section data-reveal>
                    <p class="font-mono2 text-xs tracking-wider text-blue-400">/ CONCLUSÃO</p>
                    <p class="mt-5 max-w-3xl leading-relaxed text-zinc-400">{{ $conclusion }}</p>
                </section>
            @endif
        </div>

        <div class="mt-16 border-t border-white/5 pt-8">
            <a href="{{ route('projects.index') }}" class="group inline-flex items-center gap-2 font-mono2 text-sm text-zinc-400 transition-colors hover:text-white">
                <span aria-hidden="true" class="transition-transform group-hover:-translate-x-1">←</span> voltar para todos os projetos
            </a>
        </div>
    </article>

    {{-- Projetos relacionados --}}
    @if ($related->isNotEmpty())
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-8">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ PROJETOS RELACIONADOS</p>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $rel)
                    <article data-reveal class="group flex flex-col overflow-hidden rounded-xl border border-white/10 bg-white/[0.02] transition-colors hover:border-white/20">
                        <a href="{{ $rel->hasDetailPage() ? route('projects.show', $rel) : route('projects.index') }}" class="block overflow-hidden">
                            <div class="aspect-[16/10] w-full overflow-hidden">
                                @if ($rel->image_url)
                                    <img src="{{ $rel->image_url }}" alt="Prévia do projeto {{ $rel->title }}" loading="lazy" class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]">
                                @else
                                    <div class="flex h-full w-full items-center justify-center" style="background-image: repeating-linear-gradient(135deg, #18181b 0 10px, #141417 10px 20px);"><span class="font-mono2 text-xs text-zinc-600">[ {{ $rel->title }} ]</span></div>
                                @endif
                            </div>
                        </a>
                        <div class="flex flex-1 flex-col p-6">
                            @if (! empty($rel->technologies))<p class="font-mono2 text-xs text-blue-400">{{ collect($rel->technologies)->take(2)->implode(' · ') }}</p>@endif
                            <h3 class="mt-2 font-display text-xl font-bold tracking-tight text-white"><a href="{{ $rel->hasDetailPage() ? route('projects.show', $rel) : route('projects.index') }}" class="hover:text-blue-300">{{ $rel->title }}</a></h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ \Illuminate\Support\Str::limit($rel->description, 90) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
