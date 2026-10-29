@php($link = (array) $link)

<div data-repeater-item class="grid items-end gap-3 sm:grid-cols-[1fr_2fr_auto]">
    <x-admin.input :name="'links['.$index.'][label]'" label="Rótulo" :value="$link['label'] ?? ''" />
    <x-admin.input :name="'links['.$index.'][url]'" label="URL" type="url" :value="$link['url'] ?? ''" placeholder="https://..." />
    <button type="button" data-repeater-remove class="mb-1 text-xs font-semibold text-error transition-colors hover:text-primary-hover hover:underline">Remover</button>
</div>
