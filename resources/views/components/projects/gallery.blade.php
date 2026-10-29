@props(['screenshots' => [], 'project'])

@php
    // Normaliza os slides uma vez: URL absoluta + legenda/alt, usado tanto nas miniaturas quanto no modal.
    $slides = collect($screenshots)->map(fn (array $shot): array => [
        'src' => asset($shot['src']),
        'alt' => $shot['alt'] ?? ('Screenshot do projeto '.$project->title),
        'caption' => $shot['caption'] ?? null,
    ])->values();
@endphp

@if ($slides->isNotEmpty())
    <div data-gallery>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            @foreach ($slides as $index => $slide)
                <figure class="overflow-hidden rounded-xl border dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white">
                    <button
                        type="button"
                        data-gallery-open
                        data-index="{{ $index }}"
                        class="group block w-full cursor-zoom-in"
                        aria-label="Ampliar imagem{{ $slide['caption'] ? ': '.$slide['caption'] : '' }}"
                    >
                        <span class="relative block">
                            <img
                                src="{{ $slide['src'] }}"
                                alt="{{ $slide['alt'] }}"
                                loading="lazy"
                                class="aspect-video w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.03]"
                            >
                            <span aria-hidden="true" class="absolute inset-0 grid place-items-center bg-black/0 opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100">
                                <span class="grid h-11 w-11 place-items-center rounded-full bg-white/90 text-zinc-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path><path d="M11 8v6"></path><path d="M8 11h6"></path></svg>
                                </span>
                            </span>
                        </span>
                    </button>
                    @if (! empty($slide['caption']))
                        <figcaption class="px-4 py-3 text-sm dark:text-zinc-400 text-zinc-600">{{ $slide['caption'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>

        <script type="application/json" data-gallery-data>@json($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>

        {{-- Lightbox / modal com navegação entre as imagens --}}
        <div
            data-gallery-modal
            class="fixed inset-0 z-[100] hidden items-center justify-center p-4 sm:p-8"
            role="dialog"
            aria-modal="true"
            aria-label="Galeria de screenshots do projeto {{ $project->title }}"
        >
            <div data-gallery-backdrop class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div class="relative z-10 flex max-h-full w-full max-w-5xl flex-col">
                <div class="mb-3 flex items-center justify-between gap-4 text-sm text-zinc-300">
                    <span data-gallery-counter aria-live="polite"></span>
                    <button
                        type="button"
                        data-gallery-close
                        class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/5 text-white transition hover:bg-white/15"
                        aria-label="Fechar galeria"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                    </button>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black">
                    <img
                        data-gallery-image
                        src=""
                        alt=""
                        class="mx-auto max-h-[72vh] w-full bg-black object-contain transition duration-300 ease-out"
                    >

                    {{-- Anterior --}}
                    <button
                        type="button"
                        data-gallery-prev
                        class="absolute left-3 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/50 text-white transition hover:bg-black/80"
                        aria-label="Imagem anterior"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>

                    {{-- Próxima --}}
                    <button
                        type="button"
                        data-gallery-next
                        class="absolute right-3 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/50 text-white transition hover:bg-black/80"
                        aria-label="Próxima imagem"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>
                    </button>
                </div>

                <p data-gallery-caption class="mt-3 min-h-5 text-center text-sm text-zinc-300"></p>
            </div>
        </div>
    </div>
@else
    {{-- Ainda sem screenshots: estrutura pronta para receber as imagens (config `details.screenshots`). --}}
    <div data-screenshots-empty class="grid aspect-[16/6] w-full place-items-center rounded-xl border border-dashed dark:border-zinc-800 border-zinc-300 p-6 text-center text-sm dark:text-zinc-500 text-zinc-500">
        As screenshots deste projeto serão adicionadas em breve.
    </div>
@endif
