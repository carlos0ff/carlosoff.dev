<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada aviso da régua de cobrança (único por cobrança + etapa + sequência = idempotente).
 */
#[Fillable(['charge_id', 'stage', 'sequence', 'channel', 'recipient', 'sent_at'])]
class ChargeNotice extends Model
{
    public $timestamps = false;

    public const STAGES = [
        'reminder' => 'Lembrete antes do vencimento',
        'due' => 'Aviso no vencimento',
        'overdue' => 'Aviso de atraso',
        'grace_expired' => 'Fim da tolerância (alerta interno)',
        'pre_suspension' => 'Aviso prévio de suspensão',
        'suspended' => 'Aviso de suspensão',
        'reactivated' => 'Aviso de reativação',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
