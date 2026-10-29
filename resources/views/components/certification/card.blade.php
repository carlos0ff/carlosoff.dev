@props([
    'title',
    'issuer',
    'date' => null,
    'description' => null,
    'file' => null,
    'logo' => null,
    'type' => null,
    'certificateUrl' => null,
    'credential' => null,
    'workloadHours' => null,
])

@php
    $extension = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : null;
@endphp

<article class="experience-reveal flex flex-col gap-4 dark:bg-primary-bg bg-secondary-bg border dark:border-zinc-800 border-zinc-200 rounded-md p-5 min-w-0">
    <div class="flex items-start gap-4">
        @if ($logo)
            <div class="h-12 w-12 shrink-0 overflow-clip rounded-md border dark:border-zinc-800 border-zinc-200 bg-white">
                <img src="{{ asset($logo) }}" alt="{{ $issuer }} logo" loading="lazy" width="48" height="48" class="h-full w-full object-contain">
            </div>
        @else
            {{-- Sem logo da instituição: ícone padrão (Lucide "award") para manter o mesmo padrão visual --}}
            <div data-default-icon class="grid h-12 w-12 shrink-0 place-items-center rounded-md border dark:border-zinc-800 border-zinc-200 dark:bg-zinc-900 bg-white text-primary-color" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="6"></circle>
                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                </svg>
            </div>
        @endif

        <div class="min-w-0">
            <h3 class="text-lg font-semibold leading-snug">{{ $title }}</h3>
            <p class="text-sm dark:text-zinc-400 text-zinc-600">{{ $issuer }}</p>
            @if ($date || $type || $workloadHours)
                <p class="mt-1 text-xs uppercase tracking-widest text-zinc-500">
                    {{ collect([$type, $date, $workloadHours ? $workloadHours.' h' : null])->filter()->implode(' · ') }}
                </p>
            @endif
        </div>
    </div>

    @if ($description)
        <p class="text-sm leading-relaxed dark:text-zinc-400 text-zinc-600">{{ $description }}</p>
    @endif

    @if ($credential)
        <p class="text-xs dark:text-zinc-500 text-zinc-500">Credencial: <span class="font-mono">{{ $credential }}</span></p>
    @endif

    @if ($file)
        <button
            type="button"
            data-certificate-open
            data-title="{{ $title }}"
            data-issuer="{{ $issuer }}"
            data-date="{{ $date }}"
            data-file="{{ asset($file) }}"
            data-type="{{ $extension === 'pdf' ? 'pdf' : 'image' }}"
            class="mt-auto inline-flex w-fit items-center gap-2 rounded-md border border-primary-color/50 px-4 py-2 text-sm font-semibold text-primary-color transition-colors hover:bg-primary-color hover:text-white"
        >
            Ver certificado
        </button>
    @endif

    @if ($certificateUrl)
        <a href="{{ $certificateUrl }}" target="_blank" rel="noopener noreferrer" @class(['inline-flex w-fit items-center gap-1 text-sm font-semibold transition-colors hover:text-primary-color', 'mt-auto' => ! $file])>
            Abrir certificado <span aria-hidden="true">↗</span>
        </a>
    @endif
</article>
