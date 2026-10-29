@php
    $photo = ! empty($about['photo'])
        ? (\Illuminate\Support\Str::startsWith($about['photo'], ['http://', 'https://']) ? $about['photo'] : asset($about['photo']))
        : null;
    $stackByCategory = collect($stack)->groupBy('category');
@endphp

<x-layouts.site
    :title="($about['seo_title'] ?? null) ?: 'Sobre | José Carlos'"
    :description="($about['seo_description'] ?? null) ?: ($about['headline'] ?? null)"
    :image="$photo"
    active="about"
>
    {{-- ═══ QUEM SOU ═══ --}}
    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-20 pb-8">
        <p class="font-mono2 text-xs tracking-wider text-blue-400">/ QUEM SOU</p>
        <div class="mt-8 grid items-start gap-10 lg:grid-cols-[1fr_320px]">
            <div>
                <h1 class="font-display text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">{{ $about['headline'] ?? 'José Carlos' }}</h1>
                <div class="mt-6 max-w-2xl space-y-4 text-lg leading-relaxed text-zinc-400">
                    @foreach (($about['paragraphs'] ?? []) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 font-mono2 text-xs">
                    <a href="https://github.com/carlos0ff" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-md border border-white/15 px-4 py-2 text-zinc-200 transition-colors hover:border-white/40 hover:text-white">GitHub <span aria-hidden="true">↗</span></a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-blue-500">Vamos conversar <span aria-hidden="true">→</span></a>
                </div>
            </div>

            <aside class="order-first lg:order-none">
                <div class="aspect-square w-full max-w-xs overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="Foto de {{ $about['name'] ?? 'José Carlos' }}" width="320" height="320" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full w-full place-items-center font-display text-6xl font-bold text-zinc-700">JC</div>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    {{-- ═══ STACK ═══ --}}
    @if ($stackByCategory->isNotEmpty())
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-16">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ STACK</p>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stackByCategory as $category => $items)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6">
                        <h2 class="font-display text-lg font-semibold text-white">{{ $category }}</h2>
                        <ul class="mt-4 space-y-2 font-mono2 text-sm text-zinc-400">
                            @foreach ($items as $tech)
                                <li class="flex items-center gap-2"><span class="text-blue-500">—</span> {{ $tech['name'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ EXPERIÊNCIA ═══ --}}
    @if (! empty($experiences))
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-16">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ EXPERIÊNCIA</p>
            <div class="mt-8 space-y-4">
                @foreach ($experiences as $exp)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="font-display text-lg font-semibold text-white">{{ $exp['role'] }}</h2>
                            <span class="font-mono2 text-xs text-zinc-500">{{ $exp['period'] }}</span>
                        </div>
                        <p class="mt-1 font-mono2 text-sm text-blue-400">{{ $exp['company'] }}</p>
                        @if (! empty($exp['description']))<p class="mt-3 leading-relaxed text-zinc-400">{{ $exp['description'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ FORMAÇÃO ═══ --}}
    @if (! empty($education))
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-16">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ FORMAÇÃO</p>
            <div class="mt-8 space-y-4">
                @foreach ($education as $edu)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="font-display text-lg font-semibold text-white">{{ $edu['role'] }}</h2>
                            <span class="font-mono2 text-xs text-zinc-500">{{ $edu['period'] }}</span>
                        </div>
                        <p class="mt-1 font-mono2 text-sm text-blue-400">{{ $edu['company'] }}</p>
                        @if (! empty($edu['description']))<p class="mt-3 leading-relaxed text-zinc-400">{{ $edu['description'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ CURSOS / CERTIFICAÇÕES ═══ --}}
    @if (! empty($certifications))
        <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-16">
            <p class="font-mono2 text-xs tracking-wider text-blue-400">/ CURSOS E CERTIFICAÇÕES</p>
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($certifications as $course)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6">
                        <h2 class="font-display text-base font-semibold text-white">{{ $course['name'] ?? ($course['title'] ?? '') }}</h2>
                        @if (! empty($course['institution']) || ! empty($course['issuer']))<p class="mt-1 font-mono2 text-sm text-blue-400">{{ $course['institution'] ?? $course['issuer'] }}</p>@endif
                        @if (! empty($course['description']))<p class="mt-3 text-sm leading-relaxed text-zinc-400">{{ $course['description'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
