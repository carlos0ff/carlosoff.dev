<?php

namespace App\Models;

use App\Enums\ScopeRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_project_id', 'title', 'description', 'reason', 'affected_features', 'impact_analysis', 'estimated_hours',
    'amount_cents', 'estimated_days', 'dependencies', 'acceptance_criteria', 'requested_by',
])]
class ScopeRequest extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScopeRequestStatus::class,
            'quoted_at' => 'datetime',
            'decided_at' => 'datetime',
            'amount_cents' => 'integer',
            'estimated_hours' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Texto exato apresentado ao cliente no momento da decisão; o hash prova sobre o que ele consentiu.
     */
    public function termsForConsent(): string
    {
        return implode("\n", [
            "Solicitação #{$this->id}: {$this->title}",
            "Descrição: {$this->description}",
            'Valor adicional (centavos): '.($this->amount_cents ?? 0),
            'Horas estimadas: '.($this->estimated_hours ?? '-'),
            'Prazo (dias): '.($this->estimated_days ?? '-'),
            'Critérios de aceite: '.($this->acceptance_criteria ?? '-'),
        ]);
    }
}
