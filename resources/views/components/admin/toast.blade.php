{{-- Notificações (toasts): mensagens de sucesso (`status`) e de erro (validação / `errors`) vindas da sessão. --}}
@php
    $toasts = collect();
    if (session('status')) {
        $toasts->push(['tone' => 'success', 'message' => session('status')]);
    }
    if ($errors->any()) {
        $toasts->push(['tone' => 'error', 'message' => $errors->count() === 1 ? $errors->first() : 'Não foi possível salvar: corrija os '.$errors->count().' campos destacados.']);
    }
@endphp

<div aria-live="polite" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:left-auto sm:right-6 sm:w-96">
    @foreach ($toasts as $toast)
        <div
            data-toast
            role="{{ $toast['tone'] === 'error' ? 'alert' : 'status' }}"
            @class([
                'pointer-events-auto flex w-full items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg shadow-black/30',
                'border-success-border bg-success-surface text-success' => $toast['tone'] === 'success',
                'border-error-border bg-error-surface text-primary-hover' => $toast['tone'] === 'error',
            ])
        >
            <span class="min-w-0 flex-1">{{ $toast['message'] }}</span>
            <button type="button" data-toast-close aria-label="Fechar aviso" class="shrink-0 opacity-70 transition-opacity hover:opacity-100">✕</button>
        </div>
    @endforeach
</div>
