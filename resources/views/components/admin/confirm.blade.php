{{-- Diálogo de confirmação único, acionado por qualquer <form data-confirm="..."> (ver resources/js/admin.js). --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-title" class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-xl border border-border bg-card p-0 text-inherit backdrop:bg-overlay">
    <div class="p-6">
        <h2 id="confirm-title" class="text-lg font-semibold">Excluir item?</h2>
        <p id="confirm-message" class="mt-2 text-sm text-foreground-secondary">Esta ação não poderá ser desfeita.</p>
    </div>
    <div class="flex justify-end gap-2 border-t border-border-subtle px-6 py-4">
        <button type="button" data-confirm-cancel class="rounded-md border border-border bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground transition-colors hover:border-border-hover hover:bg-secondary-hover active:bg-card-hover">Cancelar</button>
        <button type="button" data-confirm-accept class="rounded-md border border-error bg-error px-4 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:border-primary-hover hover:bg-primary-hover active:border-primary-active active:bg-primary-active">Excluir</button>
    </div>
</dialog>
