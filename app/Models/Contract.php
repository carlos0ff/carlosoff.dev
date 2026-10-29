<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'number', 'client_id', 'client_project_id', 'object', 'scope', 'deliverables', 'setup_fee_cents', 'monthly_fee_cents',
    'payment_method', 'payment_terms', 'billing_day', 'starts_at', 'ends_at', 'renewal_rules', 'clauses', 'support_sla',
])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    /**
     * Cláusulas textuais do contrato (rótulos exibidos no formulário e nos documentos).
     *
     * @var array<string, string>
     */
    public const CLAUSES = [
        'contractor_obligations' => 'Obrigações da contratada',
        'client_obligations' => 'Obrigações do cliente',
        'support_included' => 'Suporte incluído',
        'maintenance_included' => 'Manutenção incluída',
        'warranty' => 'Garantia e correções',
        'domain_hosting' => 'Domínio e hospedagem',
        'acceptance_criteria' => 'Critérios de aceite',
        'suspension_rules' => 'Regras de suspensão e reativação',
        'termination' => 'Rescisão',
        'intellectual_property' => 'Propriedade intelectual',
        'data_protection' => 'Tratamento de dados e confidencialidade',
        'schedule' => 'Cronograma',
        'scope_changes' => 'Alterações de escopo',
        'price_adjustment' => 'Reajuste',
        'late_payment' => 'Multa e juros por atraso',
        'taxes' => 'Impostos e taxas',
        'third_party_licenses' => 'Licenças de terceiros',
        'discounts' => 'Descontos',
        'not_included' => 'Serviços não incluídos',
        'support_channels' => 'Canais e horário de suporte',
        'emergency' => 'Atendimento emergencial',
        'forum' => 'Foro',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'clauses' => 'array',
            'support_sla' => 'array',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'signed_at' => 'datetime',
            'setup_fee_cents' => 'integer',
            'monthly_fee_cents' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function policy(): HasOne
    {
        return $this->hasOne(BillingPolicy::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class)->orderByDesc('version');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function clause(string $key): ?string
    {
        return $this->clauses[$key] ?? null;
    }

    public function isActiveOn(\DateTimeInterface $date): bool
    {
        return $this->status === ContractStatus::Active
            && ($this->starts_at === null || $this->starts_at->lte($date))
            && ($this->ends_at === null || $this->ends_at->gte($date));
    }
}
