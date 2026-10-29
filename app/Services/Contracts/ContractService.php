<?php

namespace App\Services\Contracts;

use App\Enums\AuditAction;
use App\Enums\ContractStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\AuditLog;
use App\Models\BillingPolicy;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContractService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ClientProject $project, array $data, ?User $user): Contract
    {
        return DB::transaction(function () use ($project, $data, $user) {
            $contract = new Contract($data);
            $contract->client_id = $project->client_id;
            $contract->client_project_id = $project->id;
            $contract->number = $data['number'] ?? $this->nextNumber();
            $contract->status = ContractStatus::Draft;
            $contract->version = 1;
            $contract->save();
            $contract->refresh();

            $contract->policy()->create(BillingPolicy::defaults());
            $this->snapshot($contract, $user, 'Versão inicial');
            $this->syncProjectStatus($project);

            AuditLog::forProject($project, AuditAction::Create, 'Contrato', $contract, $contract->number, $contract->object);

            return $contract;
        });
    }

    /**
     * Contrato vigente não é reescrito: alterações exigem aditivo (novo contrato/documento assinado).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Contract $contract, array $data, ?User $user, string $summary): Contract
    {
        if (! in_array($contract->status, [ContractStatus::Draft, ContractStatus::AwaitingSignature], true)) {
            throw new InvalidArgumentException('Contrato vigente ou encerrado não pode ser alterado; formalize um aditivo.');
        }

        return DB::transaction(function () use ($contract, $data, $user, $summary) {
            $contract->fill($data);
            $dirty = array_keys($contract->getDirty());
            if ($dirty === []) {
                return $contract;
            }

            $contract->version++;
            $contract->save();
            $this->snapshot($contract, $user, $summary);

            AuditLog::forProject($contract->project, AuditAction::Update, 'Contrato', $contract, $contract->number, "v{$contract->version}: {$summary}", ['fields' => $dirty]);

            return $contract;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePolicy(Contract $contract, array $data): BillingPolicy
    {
        $policy = $contract->policy ?? $contract->policy()->make(BillingPolicy::defaults());
        $policy->fill($data);
        $dirty = $policy->getDirty();
        $policy->save();

        AuditLog::forProject($contract->project, AuditAction::Update, 'Política financeira', $policy, $contract->number, 'Campos: '.implode(', ', array_keys($dirty)), ['changes' => $dirty]);

        return $policy;
    }

    /**
     * Ativação. Normalmente disparada pela assinatura do contrato; a ativação manual exige justificativa (ex.: assinado em papel).
     */
    public function activate(Contract $contract, string $justification): void
    {
        $contract->forceFill(['status' => ContractStatus::Active, 'signed_at' => $contract->signed_at ?? now()])->save();
        AuditLog::forProject($contract->project, AuditAction::Update, 'Contrato', $contract, $contract->number, "Ativado: {$justification}", visibleToClient: true);
        $this->syncProjectStatus($contract->project);
    }

    public function end(Contract $contract, ContractStatus $status, string $reason): void
    {
        if (! in_array($status, [ContractStatus::Ended, ContractStatus::Cancelled], true)) {
            throw new InvalidArgumentException('Status de encerramento inválido.');
        }

        $contract->forceFill(['status' => $status])->save();
        AuditLog::forProject($contract->project, AuditAction::Update, 'Contrato', $contract, $contract->number, "{$status->label()}: {$reason}", visibleToClient: true);
        $this->syncProjectStatus($contract->project);
    }

    public function syncProjectStatus(ClientProject $project): void
    {
        $contracts = $project->contracts()->get();

        $awaitingSignature = $project->documents()
            ->where('type', DocumentType::DevelopmentContract->value)
            ->whereIn('status', [DocumentStatus::AwaitingSignature->value, DocumentStatus::PartiallySigned->value])
            ->exists();

        $status = match (true) {
            $contracts->contains('status', ContractStatus::Active) => ContractStatus::Active,
            $awaitingSignature => ContractStatus::AwaitingSignature,
            $contracts->contains('status', ContractStatus::Draft) => ContractStatus::Draft,
            $contracts->contains('status', ContractStatus::Ended) => ContractStatus::Ended,
            $contracts->isNotEmpty() => $contracts->last()->status,
            default => ContractStatus::None,
        };

        $project->forceFill(['contract_status' => $status])->saveQuietly();
    }

    protected function snapshot(Contract $contract, ?User $user, string $summary): void
    {
        ContractVersion::create([
            'contract_id' => $contract->id,
            'version' => $contract->version,
            'snapshot' => $contract->only(['number', 'object', 'scope', 'deliverables', 'setup_fee_cents', 'monthly_fee_cents', 'payment_method', 'payment_terms', 'billing_day', 'starts_at', 'ends_at', 'renewal_rules', 'clauses', 'support_sla']),
            'created_by' => $user?->id,
            'change_summary' => $summary,
        ]);
    }

    protected function nextNumber(): string
    {
        $year = now()->format('Y');
        $sequence = Contract::where('number', 'like', "CT-{$year}-%")->count() + 1;

        do {
            $number = sprintf('CT-%s-%04d', $year, $sequence++);
        } while (Contract::where('number', $number)->exists());

        return $number;
    }
}
