@props([
    'experiences',
    'limit' => null,
    'showAllLink' => false,
    'title' => 'Work Experience',
    'id' => 'experience',
])

@php
    $visible = $limit ? array_slice($experiences, 0, $limit) : $experiences;
@endphp

<section id="{{ $id }}" {{ $attributes }}>
    <div class="mb-16">
        <h2 class="font-incognito text-4xl mb-4 font-bold tracking-tight">{{ $title }}</h2>
    </div>

    <x-experience.timeline :experiences="$visible" />

    @if ($showAllLink)
        <div class="mt-14 flex justify-center">
            <a
                href="{{ route('experience') }}"
                class="group inline-flex items-center gap-2 rounded-md border border-primary-color/50 px-6 py-3 text-sm font-semibold text-primary-color transition-colors hover:bg-primary-color hover:text-white"
            >
                Ver todas as experiências
                <span aria-hidden="true" class="transition-transform duration-300 group-hover:translate-x-1">→</span>
            </a>
        </div>
    @endif
</section>
