@props([
    'certifications',
    'title' => 'Cursos e Certificações',
    'id' => 'certifications',
])

<section id="{{ $id }}" {{ $attributes }}>
    <div class="mb-16">
        <h2 class="font-incognito text-4xl mb-4 font-bold tracking-tight">{{ $title }}</h2>
        <p class="max-w-2xl text-base dark:text-zinc-400 text-zinc-600 leading-relaxed">
            Cursos e certificados que complementam minha formação. Clique em “Ver certificado” para abrir o documento.
        </p>
    </div>

    @if (count($certifications) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($certifications as $certification)
                <x-certification.card
                    :title="$certification['title']"
                    :issuer="$certification['issuer']"
                    :date="$certification['date'] ?? null"
                    :description="$certification['description'] ?? null"
                    :file="$certification['file'] ?? null"
                    :logo="$certification['logo'] ?? null"
                    :type="$certification['type'] ?? null"
                    :certificate-url="$certification['certificate_url'] ?? null"
                    :credential="$certification['credential'] ?? null"
                    :workload-hours="$certification['workload_hours'] ?? null"
                />
            @endforeach
        </div>
    @else
        <div class="dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md p-8 text-center dark:text-zinc-400 text-zinc-600">
            Seus cursos e certificações aparecerão aqui.
        </div>
    @endif

    {{-- Modal único reaproveitado por todos os cards (preenchido via resources/js/app.js) --}}
    <dialog
        id="certificate-modal"
        aria-labelledby="certificate-modal-title"
        class="m-auto w-[min(56rem,calc(100vw-2rem))] max-h-[90vh] overflow-hidden rounded-md border dark:border-zinc-800 border-zinc-200 dark:bg-primary-bg bg-secondary-bg p-0 text-inherit backdrop:bg-black/70"
    >
        <div class="flex items-start justify-between gap-4 border-b dark:border-zinc-800 border-zinc-200 p-4">
            <div class="min-w-0">
                <h3 id="certificate-modal-title" class="text-lg font-semibold leading-snug"></h3>
                <p id="certificate-modal-meta" class="text-sm dark:text-zinc-400 text-zinc-600"></p>
            </div>
            <button type="button" data-certificate-close aria-label="Fechar" class="shrink-0 rounded-md border dark:border-zinc-800 border-zinc-200 px-3 py-1 text-sm transition-colors hover:border-primary-color hover:text-primary-color">
                Fechar
            </button>
        </div>

        <div id="certificate-modal-body" class="flex max-h-[75vh] items-center justify-center overflow-auto bg-black/20 p-4"></div>
    </dialog>
</section>
