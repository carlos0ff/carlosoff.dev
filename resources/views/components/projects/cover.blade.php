@props(['project'])

@php
    // Iniciais do fallback: "PicPay Wallet" → PW, "WorkFacilit" → WF, "VigiaSUS" → VS, "Realizze" → RE.
    $words = \Illuminate\Support\Str::of($project->title)->explode(' ')->filter()->values();
    $parts = $words->count() > 1
        ? $words
        : collect(preg_split('/(?<=[a-z])(?=[A-Z])/u', (string) $words->first()))->filter()->values();
    $initials = filled($project->acronym) ? mb_strtoupper($project->acronym) : mb_strtoupper($parts->count() > 1
        ? $parts->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')
        : mb_substr((string) $parts->first(), 0, 2));
@endphp

@if ($project->image_url)
    <img
        src="{{ $project->image_url }}"
        alt="Pré-visualização do projeto {{ $project->title }}"
        loading="lazy"
        {{ $attributes->class('h-full w-full object-cover object-top') }}
    >
@else
    <div
        data-project-fallback
        aria-hidden="true"
        {{ $attributes->class('grid h-full w-full place-items-center bg-gradient-to-br dark:from-primary-color/20 dark:via-zinc-800/60 dark:to-zinc-900 from-primary-color/10 via-zinc-100 to-zinc-200') }}
    >
        <span class="font-incognito text-6xl font-bold tracking-tight dark:text-zinc-500 text-zinc-400">{{ $initials }}</span>
    </div>
@endif
