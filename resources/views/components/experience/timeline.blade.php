@props(['experiences'])

<div {{ $attributes->class('grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-10') }}>
    @foreach ($experiences as $experience)
        <x-experience.item
            :company="$experience['company']"
            :role="$experience['role'] ?? null"
            :period="$experience['period'] ?? null"
            :description="$experience['description'] ?? null"
            :logo="$experience['logo'] ?? null"
            :url="$experience['url'] ?? null"
            :skills="$experience['skills'] ?? []"
            :current="$experience['current'] ?? false"
            :location="$experience['location'] ?? null"
            :responsibilities="$experience['responsibilities'] ?? []"
            :results="$experience['results'] ?? []"
            :index="$loop->index"
            :connector="$loop->last ? 'none' : ($loop->remaining === 1 ? 'lg-hidden' : 'full')"
        />
    @endforeach
</div>
