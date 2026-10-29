@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.portal title="Cobranças">
    <x-admin.page-header title="Cobranças" subtitle="Valores, vencimentos e pagamentos confirmados." />
    <div class="space-y-4">
        @forelse ($charges as $charge)
            <x-admin.card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $charge->description }}</p>
                        <p class="text-sm text-foreground-secondary">{{ $charge->project->name }} · vencimento {{ $charge->due_date->format('d/m/Y') }}</p>
                        @if ($charge->isDisputed())<x-admin.badge tone="info">contestação em análise</x-admin.badge>@endif
                    </div>
                    <div class="text-right"><p class="text-lg font-bold tabular-nums">{{ $money($charge->amount_cents) }}</p><x-admin.status :value="$charge->status" /></div>
                </div>
                @if ($charge->paid_cents > 0)<p class="mt-2 text-sm text-success">Recebido: {{ $money($charge->paid_cents) }}</p>@endif
                @if ($charge->payment_url && $charge->isOpen())<x-admin.button class="mt-3" size="sm" variant="primary" :href="$charge->payment_url" target="_blank" rel="noopener">Pagar</x-admin.button>@endif
                @if ($charge->isOpen())
                    <details class="mt-3 text-sm">
                        <summary class="cursor-pointer text-foreground-secondary">Já paguei / quero contestar</summary>
                        <div class="mt-3 grid gap-4 md:grid-cols-2">
                            <form method="POST" action="{{ route('portal.charges.proof', $charge) }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf
                                <x-admin.field name="file" label="Enviar comprovante"><input id="f-file" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" required class="block w-full text-sm"></x-admin.field>
                                <x-admin.input name="notes" label="Observação" />
                                <x-admin.button type="submit" size="sm">Enviar comprovante</x-admin.button>
                                <p class="text-xs text-foreground-muted">A baixa é feita após a conferência pela nossa equipe.</p>
                            </form>
                            @unless ($charge->isDisputed())
                                <form method="POST" action="{{ route('portal.charges.dispute', $charge) }}" class="space-y-2">
                                    @csrf
                                    <x-admin.textarea name="reason" label="Contestar cobrança" rows="3" required />
                                    <x-admin.button type="submit" size="sm" variant="ghost">Registrar contestação</x-admin.button>
                                </form>
                            @endunless
                        </div>
                    </details>
                @endif
            </x-admin.card>
        @empty
            <x-admin.card><p class="text-sm text-foreground-muted">Nenhuma cobrança.</p></x-admin.card>
        @endforelse
    </div>
    <x-admin.pagination :paginator="$charges" />
</x-layouts.portal>
