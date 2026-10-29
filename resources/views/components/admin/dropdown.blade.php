{{-- Menu "⋮" sem JS obrigatório (<details>); admin.js fecha ao clicar fora ou apertar Esc. --}}
@props(['label' => 'Mais ações'])

<details data-dropdown {{ $attributes->class('relative') }}>
    <summary aria-label="{{ $label }}" title="{{ $label }}" class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-md border border-border bg-secondary text-base leading-none text-foreground-secondary transition-colors hover:border-border-hover hover:bg-secondary-hover hover:text-foreground [&::-webkit-details-marker]:hidden">⋮</summary>

    <div class="absolute right-0 z-30 mt-1 w-52 rounded-lg border border-border bg-card p-1 text-left shadow-lg shadow-black/30">
        {{ $slot }}
    </div>
</details>
