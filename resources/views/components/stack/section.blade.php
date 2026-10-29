@props([
    'items',
    'title' => 'Competências',
    'id' => 'stack',
])

<section id="{{ $id }}" {{ $attributes }}>
    <div class="mb-8">

        <h2 class="font-incognito text-4xl font-bold tracking-tight">{{ $title }}</h2>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="cpu" aria-hidden="true" class="lucide lucide-cpu w-4 h-4 text-brand-blue">
            <path d="M12 20v2"></path><path d="M12 2v2"></path>
            <path d="M17 20v2"></path><path d="M17 2v2"></path>
            <path d="M2 12h2"></path>
            <path d="M2 17h2"></path>
            <path d="M2 7h2"></path>
            <path d="M20 12h2"></path>
            <path d="M20 17h2"></path>
            <path d="M20 7h2"></path>
            <path d="M7 20v2"></path>
            <path d="M7 2v2"></path>
            
            <rect x="4" y="4" width="16" height="16" rx="2"></rect>
            <rect x="8" y="8" width="8" height="8" rx="1"></rect>
        </svg>
    </div>

    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($items as $item)
            <x-stack.item
                :name="$item['name']"
                :icon="$item['icon'] ?? null"
                :invert="$item['invert'] ?? false"
                :index="$loop->index"
            />
        @endforeach
    </ul>
</section>
