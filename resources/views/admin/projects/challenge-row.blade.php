@php($challenge = (array) $challenge)

<div data-repeater-item class="rounded-lg border border-border p-4">
    <div class="grid gap-4">
        <x-admin.input :name="'challenges['.$index.'][title]'" label="Desafio (título)" :value="$challenge['title'] ?? ''" />
        <x-admin.textarea :name="'challenges['.$index.'][problem]'" label="Problema" :value="$challenge['problem'] ?? ''" :rows="3" />
        <x-admin.textarea :name="'challenges['.$index.'][solution]'" label="Solução" :value="$challenge['solution'] ?? ''" :rows="3" />
    </div>
    <button type="button" data-repeater-remove class="mt-3 text-xs font-semibold text-error transition-colors hover:text-primary-hover hover:underline">Remover desafio</button>
</div>
