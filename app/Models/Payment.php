<?php

namespace App\Models;

use App\Enums\PaymentSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['charge_id', 'amount_cents', 'paid_at', 'method', 'source', 'gateway_payment_id', 'evidence_path', 'evidence_notes', 'recorded_by'])]
class Payment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'paid_at' => 'datetime', 'source' => PaymentSource::class];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isRefund(): bool
    {
        return $this->amount_cents < 0;
    }
}
