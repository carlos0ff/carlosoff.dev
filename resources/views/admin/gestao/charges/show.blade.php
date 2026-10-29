@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.admin :title="$charge->description" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Cobranças' => route('admin.gestao.charges.index'), $charge->description => null]">
    <x-admin.page-header :title="$charge->description" :subtitle="$charge->project->name.' — '.$charge->project->client->displayName()">
        <x-admin.status :value="$charge->status" />
    </x-admin.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-admin.stat label="Valor" :value="$money($charge->amount_cents)" />
        <x-admin.stat label="Recebido (confirmado)" :value="$money($charge->paid_cents)" />
        <x-admin.stat label="Em aberto" :value="$money($charge->outstandingCents())" />
        <x-admin.stat label="Vencimento" :value="$charge->due_date->format('d/m/Y')" :hint="$charge->daysOverdue() ? $charge->daysOverdue().' dia(s) em atraso' : null" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Pagamentos">
                <ul class="space-y-2 text-sm">
                    @forelse ($charge->payments as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-2">
                            <span>{{ $payment->paid_at->format('d/m/Y H:i') }} · {{ $payment->method ?? '—' }} · <x-admin.status :value="$payment->source" /> {{ $payment->recorder?->name ? 'por '.$payment->recorder->name : '' }}
                                @if ($payment->evidence_notes)<span class="block text-xs text-foreground-muted">{{ $payment->evidence_notes }}</span>@endif</span>
                            <span @class(['tabular-nums font-semibold', 'text-error' => $payment->isRefund()])>{{ $money($payment->amount_cents) }}</span>
                        </li>
                    @empty
                        <li class="text-foreground-muted">Nenhum pagamento confirmado.</li>
                    @endforelse
                </ul>
            </x-admin.card>

            @if ($guard)
                <x-admin.card title="Verificações para restrição contratual">
                    <ul class="space-y-1 text-sm">
                        @foreach ($guard['checks'] as $check)
                            <li class="{{ $check['passed'] ? 'text-success' : 'text-foreground-secondary' }}">{{ $check['passed'] ? '✓' : '✕' }} {{ $check['label'] }} — <span class="text-foreground-muted">{{ $check['detail'] }}</span></li>
                        @endforeach
                    </ul>
                    @can('gestao.finance')
                        @if ($guard['allowed'])
                            <form method="POST" action="{{ route('admin.gestao.charges.propose-suspension', $charge) }}" class="mt-4" data-confirm="Será criada uma proposta de restrição que ainda precisa da aprovação de um administrador." data-confirm-title="Propor restrição?" data-confirm-button="Propor">@csrf<x-admin.button type="submit" size="sm" variant="danger">Propor restrição prevista em contrato</x-admin.button></form>
                        @else
                            <p class="mt-3 text-xs text-foreground-muted">Restrição indisponível enquanto alguma verificação não for atendida.</p>
                        @endif
                    @endcan
                </x-admin.card>
            @endif

            <x-admin.card title="Régua de cobrança (avisos enviados)">
                <ul class="space-y-1 text-sm">
                    @forelse ($charge->notices->sortBy('sent_at') as $notice)
                        <li>{{ $notice->sent_at->format('d/m/Y H:i') }} · {{ \App\Models\ChargeNotice::STAGES[$notice->stage] ?? $notice->stage }} #{{ $notice->sequence }} {{ $notice->recipient ? '→ '.$notice->recipient : '' }}</li>
                    @empty
                        <li class="text-foreground-muted">Nenhum aviso enviado.</li>
                    @endforelse
                </ul>
            </x-admin.card>

            @if ($commands->isNotEmpty())
                <x-admin.card title="Comandos ligados a esta cobrança">
                    @foreach ($commands as $command)
                        <p class="text-sm"><x-admin.status :value="$command->type" /> <x-admin.status :value="$command->status" /> {{ $command->created_at->format('d/m/Y H:i') }} — {{ $command->triggered_by_label }}</p>
                    @endforeach
                </x-admin.card>
            @endif

            <x-admin.card title="Comprovantes enviados">
                @forelse ($attachments as $attachment)
                    <a href="{{ route('admin.gestao.attachments.download', $attachment) }}" class="block text-sm hover:text-primary-hover">{{ $attachment->label }} ({{ $attachment->created_at->format('d/m/Y H:i') }})</a>
                @empty
                    <p class="text-sm text-foreground-muted">Nenhum comprovante.</p>
                @endforelse
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Detalhes">
                <x-admin.kv class="!grid-cols-1" :items="[
                    'Tipo' => $charge->kind->label(),
                    'Contrato' => $charge->contract ? '<a class=\'hover:underline\' href=\''.route('admin.gestao.contracts.show', $charge->contract).'\'>'.e($charge->contract->number).'</a>' : 'Sem contrato (não permite restrição)',
                    'Competência' => e($charge->competence),
                    'Gateway' => e($charge->gateway),
                    'Contestação' => $charge->isDisputed() ? e($charge->dispute_reason) : null,
                    'Negociação' => $charge->negotiation_until ? 'até '.$charge->negotiation_until->format('d/m/Y').' — '.e($charge->negotiation_notes) : null,
                    'Restrição possível a partir de' => $charge->suspension_eligible_at?->format('d/m/Y'),
                ]" />
            </x-admin.card>

            @can('gestao.finance')
                @if ($charge->isOpen())
                    <x-admin.card title="Registrar pagamento (baixa manual)">
                        <form method="POST" action="{{ route('admin.gestao.charges.payments.store', $charge) }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <x-admin.input name="amount" label="Valor recebido (R$)" :value="\App\Support\Money::toInput($charge->outstandingCents())" required />
                            <x-admin.input name="paid_at" label="Data do pagamento" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />
                            <x-admin.select name="method" label="Forma" :options="['pix' => 'Pix', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro', 'outro' => 'Outro']" />
                            <x-admin.field name="evidence" label="Comprovante"><input id="f-evidence" type="file" name="evidence" accept=".pdf,.png,.jpg,.jpeg" class="block w-full text-sm"></x-admin.field>
                            <x-admin.textarea name="evidence_notes" label="Evidência / observação" rows="2" help="Obrigatório sem comprovante: ex. E2E ID do Pix e conta de destino." />
                            <x-admin.button type="submit" size="sm" variant="primary">Confirmar recebimento</x-admin.button>
                        </form>
                    </x-admin.card>

                    <x-admin.card title="Contestação e negociação">
                        @if ($charge->isDisputed())
                            <form method="POST" action="{{ route('admin.gestao.charges.dispute.resolve', $charge) }}" class="space-y-2">@csrf<x-admin.textarea name="resolution" label="Resolução da contestação" rows="2" required /><x-admin.button type="submit" size="sm">Resolver contestação</x-admin.button></form>
                        @else
                            <form method="POST" action="{{ route('admin.gestao.charges.dispute', $charge) }}" class="space-y-2">@csrf<x-admin.textarea name="reason" label="Registrar contestação" rows="2" required /><x-admin.button type="submit" size="sm">Registrar</x-admin.button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.gestao.charges.negotiate', $charge) }}" class="mt-4 space-y-2 border-t border-border-subtle pt-4">@csrf
                            <x-admin.input name="until" label="Negociação vigente até" type="date" required />
                            <x-admin.textarea name="notes" label="Condições negociadas" rows="2" required />
                            <x-admin.button type="submit" size="sm">Registrar negociação</x-admin.button>
                        </form>
                    </x-admin.card>
                @endif

                <x-admin.card title="Ajustes">
                    @if ($charge->paid_cents > 0)
                        <form method="POST" action="{{ route('admin.gestao.charges.refund', $charge) }}" class="mb-4 space-y-2" data-confirm="O valor será subtraído do recebido." data-confirm-title="Registrar estorno?" data-confirm-button="Estornar">@csrf
                            <x-admin.input name="amount" label="Estornar (R$)" required />
                            <x-admin.input name="reason" label="Motivo" required />
                            <x-admin.button type="submit" size="sm" variant="danger">Estornar</x-admin.button>
                        </form>
                    @elseif ($charge->isOpen())
                        <form method="POST" action="{{ route('admin.gestao.charges.cancel', $charge) }}" class="space-y-2" data-confirm="A cobrança deixará de ser exigida." data-confirm-title="Cancelar cobrança?" data-confirm-button="Cancelar cobrança">@csrf
                            <x-admin.input name="reason" label="Motivo do cancelamento" required />
                            <x-admin.button type="submit" size="sm" variant="danger">Cancelar cobrança</x-admin.button>
                        </form>
                    @else
                        <p class="text-sm text-foreground-muted">Sem ajustes disponíveis.</p>
                    @endif
                </x-admin.card>
            @endcan
        </div>
    </div>
</x-layouts.admin>
