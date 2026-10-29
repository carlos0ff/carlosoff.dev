{{-- Upload de imagem com pré-visualização do arquivo atual/novo e opção de remover. --}}
@props(['name', 'label', 'current' => null, 'accept' => 'image/png,image/jpeg,image/webp', 'help' => null, 'removeName' => null, 'multiple' => false])

@php
    $key = str_replace(['[', ']'], ['.', ''], rtrim($name, '[]'));
    $removeName ??= 'remove_'.rtrim($name, '[]');
    $currentUrl = $current ? (\Illuminate\Support\Str::startsWith($current, ['http://', 'https://']) ? $current : asset($current)) : null;
    $isPdf = $current && \Illuminate\Support\Str::endsWith(strtolower($current), '.pdf');
@endphp

<x-admin.field :name="$key" :label="$label" :help="$help" {{ $attributes->only('class') }}>
    <div class="flex flex-wrap items-start gap-4">
        @if ($currentUrl && ! $isPdf)
            <img data-file-current src="{{ $currentUrl }}" alt="Arquivo atual" class="h-20 w-28 rounded-md border border-border bg-white object-contain">
        @elseif ($isPdf)
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener" class="rounded-md border border-border bg-secondary px-3 py-2 text-sm font-medium text-secondary-foreground transition-colors hover:border-border-hover hover:bg-secondary-hover">Ver PDF atual ↗</a>
        @endif

        <img data-file-preview hidden alt="Pré-visualização" class="h-20 w-28 rounded-md border border-primary bg-white object-contain">

        <div class="min-w-0 flex-1 space-y-2">
            <input
                id="f-{{ $key }}"
                type="file"
                name="{{ $name }}"
                accept="{{ $accept }}"
                data-file-input
                @if ($multiple) multiple @endif
                class="block w-full text-sm file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-border file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-secondary-foreground hover:file:border-border-hover hover:file:bg-secondary-hover"
            >

            @if ($current)
                <label class="flex items-center gap-2 text-xs text-foreground-secondary">
                    <input type="checkbox" name="{{ $removeName }}" value="1" @checked(old($removeName)) class="accent-primary">
                    Remover arquivo atual
                </label>
            @endif
        </div>
    </div>
</x-admin.field>
