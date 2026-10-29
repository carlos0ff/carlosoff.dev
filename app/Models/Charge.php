<?php

namespace App\Models;

use App\Enums\ChargeKind;
use App\Enums\ChargeStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\ChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'client_id', 'client_project_id', 'contract_id', 'scope_request_id', 'kind', 'description', 'competence', 'recurrence_key',
    'amount_cents', 'due_date', 'gateway', 'created_by',
])]
class Charge extends Model
{
    /** @use HasFactory<ChargeFactory> */
    use HasFactory, HasUuid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ChargeKind::class,
            'status' => ChargeStatus::class,
            'amount_cents' => 'integer',
            'paid_cents' => 'integer',
            'due_date' => 'date',
            'disputed_at' => 'datetime',
            'negotiation_until' => 'date',
            'suspension_eligible_at' => 'date',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(ChargeNotice::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Cobranças que ainda esperam dinheiro (pendente, parcial ou vencida).
     *
     * @param  Builder<Charge>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [ChargeStatus::Pending->value, ChargeStatus::PartiallyPaid->value, ChargeStatus::Overdue->value]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [ChargeStatus::Pending, ChargeStatus::PartiallyPaid, ChargeStatus::Overdue], true);
    }

    public function outstandingCents(): int
    {
        return max(0, $this->amount_cents - $this->paid_cents);
    }

    public function daysOverdue(?Carbon $today = null): int
    {
        $today ??= Carbon::today();

        return $this->due_date->lt($today) ? (int) $this->due_date->diffInDays($today) : 0;
    }

    public function isDisputed(): bool
    {
        return $this->disputed_at !== null;
    }

    public function isUnderNegotiation(?Carbon $today = null): bool
    {
        return $this->negotiation_until !== null && $this->negotiation_until->gte($today ?? Carbon::today());
    }
}
