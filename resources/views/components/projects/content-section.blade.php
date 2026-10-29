@props(['title', 'index' => 0, 'section'])

@php
    $paragraphs = $section['paragraphs'] ?? [];
    $items = $section['items'] ?? [];
    $note = $section['note'] ?? null;
@endphp

<x-projects.detail-section :title="$title" :index="$index">
    @if ($paragraphs)
        <div class="space-y-4 leading-relaxed dark:text-zinc-400 text-zinc-600">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    @endif

    @if ($items)
        <ul @class(['space-y-3 dark:text-zinc-400 text-zinc-600', 'mt-6' => $paragraphs])>
            @foreach ($items as $item)
                <li class="flex gap-3 leading-relaxed">
                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-color"></span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($note)
        <p class="mt-6 rounded-lg border-l-2 border-primary-color dark:bg-white/5 bg-zinc-100 px-4 py-3 text-sm leading-relaxed dark:text-zinc-400 text-zinc-600">{{ $note }}</p>
    @endif

    {{ $slot }}
</x-projects.detail-section>
