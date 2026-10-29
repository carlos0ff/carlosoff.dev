<?php

namespace App\Models;

use App\Enums\ReactivationMode;
use App\Enums\RestrictionMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Régua de cobrança e regras de suspensão definidas POR CONTRATO (nenhum prazo é tratado como regra legal universal).
 */
#[Fillable([
    'reminder_days_before', 'overdue_notice_interval_days', 'grace_days', 'pre_suspension_notice_days',
    'suspension_allowed', 'restriction_mode', 'suspension_requires_approval', 'reactivation_mode',
    'contract_clause_reference', 'regularization_instructions',
])]
class BillingPolicy extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspension_allowed' => 'boolean',
            'suspension_requires_approval' => 'boolean',
            'restriction_mode' => RestrictionMode::class,
            'reactivation_mode' => ReactivationMode::class,
            'reminder_days_before' => 'integer',
            'overdue_notice_interval_days' => 'integer',
            'grace_days' => 'integer',
            'pre_suspension_notice_days' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return config('gestao.policy_defaults') + [
            'suspension_allowed' => false,
            'restriction_mode' => RestrictionMode::None->value,
            'suspension_requires_approval' => true,
            'reactivation_mode' => 'automatic',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function permitsSuspension(): bool
    {
        return $this->suspension_allowed && $this->restriction_mode !== RestrictionMode::None;
    }
}
