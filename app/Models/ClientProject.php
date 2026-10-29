<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\DevelopmentStatus;
use App\Enums\FinancialStatus;
use App\Enums\OperationalStatus;
use App\Enums\RestrictionMode;
use App\Models\Concerns\HasUuid;
use Database\Factories\ClientProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Projeto de software desenvolvido para um cliente (não confundir com App\Models\Project, do portfólio).
 */
#[Fillable([
    'client_id', 'technical_lead_id', 'name', 'description', 'objective', 'technologies', 'repository_url',
    'primary_domain', 'staging_url', 'production_url', 'started_at', 'due_at', 'published_at',
    'development_status', 'plan_name', 'setup_fee_cents', 'monthly_fee_cents', 'billing_day',
    'monitoring_enabled', 'suspension_hold_until', 'suspension_hold_reason', 'internal_notes',
])]
class ClientProject extends Model
{
    /** @use HasFactory<ClientProjectFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'development_status' => DevelopmentStatus::class,
            'contract_status' => ContractStatus::class,
            'financial_status' => FinancialStatus::class,
            'operational_status' => OperationalStatus::class,
            'active_restriction' => RestrictionMode::class,
            'started_at' => 'date',
            'due_at' => 'date',
            'published_at' => 'date',
            'suspension_hold_until' => 'date',
            'agent_last_seen_at' => 'datetime',
            'monitoring_enabled' => 'boolean',
            'setup_fee_cents' => 'integer',
            'monthly_fee_cents' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function technicalLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_lead_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', ContractStatus::Active->value)->latestOfMany();
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(ProjectValidation::class);
    }

    public function scopeRequests(): HasMany
    {
        return $this->hasMany(ScopeRequest::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(RemoteCommand::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(HealthCheck::class)->latestOfMany('checked_at');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(MonitoringIncident::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function hostingServices(): HasMany
    {
        return $this->hasMany(HostingService::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function hasSuspensionHold(): bool
    {
        return $this->suspension_hold_until !== null && $this->suspension_hold_until->endOfDay()->isFuture();
    }

    /**
     * Restrição ativa no lado central que impede novas solicitações não incluídas no contrato.
     */
    public function blocksNewRequests(): bool
    {
        return in_array($this->active_restriction, [RestrictionMode::BlockNewRequests, RestrictionMode::SuspendAdditionalServices], true)
            || $this->operational_status === OperationalStatus::Suspended;
    }
}
