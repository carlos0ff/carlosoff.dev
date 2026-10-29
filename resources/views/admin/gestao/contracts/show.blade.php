@php($money = fn ($cents) => \App\Support\Money::format($cents))
@php($policy = $contract->policy)
<x-layouts.admin :title="$contract->number" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Contratos' => route('admin.gestao.contracts.index'), $contract->number => null]">
    <x-admin.page-header :title="'Contrato '.$contract->number" :subtitle="$contract->project->name.' — '.$contract->client->displayName()">
        <x-admin.status :value="$contract->status" />
        @if (in_array($contract->status->value, ['draft', 'awaiting_signature'], true))
            <x-admin.button :href="route('admin.gestao.contracts.edit', $contract)">Editar</x-admin.button>
        @endif
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Termos">
                <x-admin.kv :items="[
                    'Objeto' => e($contract->object),
                    'Versão' => 'v'.$contract->version,
                    'Implantação' => $money($contract->setup_fee_cents),
                    'Mensalidade' => $money($contract->monthly_fee_cents),
                    'Forma de pagamento' => e($contract->payment_method),
                    'Vencimento' => $contract->billing_day ? 'dia '.$contract->billing_day : null,
                    'Vigência' => ($contract->starts_at?->format('d/m/Y') ?? '—').' a '.($contract->ends_at?->format('d/m/Y') ?? 'indeterminado'),
                    'Assinado em' => $contract->signed_at?->format('d/m/Y H:i'),
                    'Escopo' => nl2br(e($contract->scope)),
                    'Entregáveis' => nl2br(e($contract->deliverables)),
                    'Condições de pagamento' => nl2br(e($contract->payment_terms)),
                    'Renovação' => nl2br(e($contract->renewal_rules)),
                ]" />
            </x-admin.card>

            <x-admin.card title="Cláusulas">
                <div class="space-y-4 text-sm">
                    @foreach (\App\Models\Contract::CLAUSES as $key => $label)
                        <div><p class="font-semibold">{{ $label }}</p><p class="text-foreground-secondary">{!! nl2br(e($contract->clause($key) ?? '—')) !!}</p></div>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card title="Documentos vinculados">
                @forelse ($contract->documents as $document)
                    <a href="{{ route('admin.gestao.documents.show', $document) }}" class="flex items-center justify-between gap-2 py-1 text-sm hover:text-primary-hover">{{ $document->title }} v{{ $document->version }} <x-admin.status :value="$document->status" /></a>
                @empty
                    <p class="text-sm text-foreground-muted">Gere o contrato de desenvolvimento na página do projeto para enviar à assinatura.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Histórico de versões">
                <ul class="space-y-2 text-sm">
                    @foreach ($contract->versions as $version)
                        <li>v{{ $version->version }} — {{ $version->created_at->format('d/m/Y H:i') }} · {{ $version->author?->name ?? 'Sistema' }} · {{ $version->change_summary }}</li>
                    @endforeach
                </ul>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Política financeira" id="politica">
                <p class="mb-4 text-xs text-foreground-muted">Prazos definidos para este contrato, não regra legal universal. Por padrão o contrato não prevê suspensão.</p>
                @can('gestao.finance')
                    <form method="POST" action="{{ route('admin.gestao.contracts.policy', $contract) }}" class="space-y-3" data-policy-form>
                        @csrf @method('PUT')
                        <x-admin.input name="reminder_days_before" label="Lembrete (dias antes do vencimento)" type="number" :value="$policy?->reminder_days_before" required />
                        <x-admin.input name="overdue_notice_interval_days" label="Intervalo entre avisos de atraso (dias)" type="number" :value="$policy?->overdue_notice_interval_days" required />
                        <x-admin.input name="grace_days" label="Tolerância após vencimento (dias)" type="number" :value="$policy?->grace_days" required help="Durante a tolerância o serviço é mantido." />
                        <x-admin.input name="pre_suspension_notice_days" label="Aviso prévio antes da restrição (dias)" type="number" :value="$policy?->pre_suspension_notice_days" required />
                        <x-admin.toggle name="suspension_allowed" label="Contrato prevê restrição por inadimplência" :checked="$policy?->suspension_allowed" />
                        <x-admin.select name="restriction_mode" label="Forma de restrição" :options="\App\Enums\RestrictionMode::options()" :value="$policy?->restriction_mode?->value" />
                        <x-admin.input name="contract_clause_reference" label="Cláusula que autoriza" :value="$policy?->contract_clause_reference" help="Ex.: Cláusula 9.2" />
                        <x-admin.toggle name="suspension_requires_approval" label="Exigir aprovação humana" :checked="$policy?->suspension_requires_approval ?? true" />
                        <x-admin.select name="reactivation_mode" label="Reativação" :options="\App\Enums\ReactivationMode::options()" :value="$policy?->reactivation_mode?->value" />
                        <x-admin.textarea name="regularization_instructions" label="Como regularizar (enviado ao cliente)" :value="$policy?->regularization_instructions" rows="3" />
                        <x-admin.button type="submit" size="sm">Salvar política</x-admin.button>
                    </form>
                @else
                    <x-admin.kv :items="['Tolerância' => $policy?->grace_days.' dias', 'Restrição' => $policy?->restriction_mode?->label()]" />
                @endcan
            </x-admin.card>

            @can('gestao.admin')
                <x-admin.card title="Situação do contrato">
                    @if ($contract->status->value !== 'active')
                        <form method="POST" action="{{ route('admin.gestao.contracts.activate', $contract) }}" class="mb-4 space-y-2">
                            @csrf
                            <x-admin.input name="justification" label="Ativar manualmente" help="Use só quando a assinatura ocorreu fora do sistema (ex.: papel). Fica auditado." />
                            <x-admin.button type="submit" size="sm">Ativar</x-admin.button>
                        </form>
                    @endif
                    @if (in_array($contract->status->value, ['active', 'draft', 'awaiting_signature'], true))
                        <form method="POST" action="{{ route('admin.gestao.contracts.end', $contract) }}" class="space-y-2" data-confirm="As cobranças recorrentes deixam de ser geradas." data-confirm-title="Encerrar contrato?" data-confirm-button="Encerrar">
                            @csrf
                            <x-admin.select name="status" label="Encerrar como" :options="['ended' => 'Encerrado', 'cancelled' => 'Cancelado']" />
                            <x-admin.input name="reason" label="Motivo" required />
                            <x-admin.button type="submit" size="sm" variant="danger">Encerrar</x-admin.button>
                        </form>
                    @endif
                </x-admin.card>
            @endcan
        </div>
    </div>
</x-layouts.admin>
