<?php

namespace App\Models;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\RestrictionMode;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_project_id', 'charge_id', 'reactivates_id', 'type', 'status', 'channel', 'idempotency_key', 'request_hash', 'restriction_mode',
    'reason', 'message_to_users', 'starts_at', 'ends_at', 'guard_report', 'triggered_by_type', 'triggered_by_id', 'triggered_by_label',
])]
class RemoteCommand extends Model
{
    use HasUuid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommandType::class,
            'status' => CommandStatus::class,
            'restriction_mode' => RestrictionMode::class,
            'guard_report' => 'array',
            'result' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'delivered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPendingForAgent(): bool
    {
        return $this->channel === 'agent' && in_array($this->status, [CommandStatus::Queued, CommandStatus::Delivered], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [CommandStatus::Executed, CommandStatus::Rejected, CommandStatus::Failed, CommandStatus::Cancelled], true);
    }

    /**
     * Formato entregue ao agente (sem dados internos).
     *
     * @return array<string, mixed>
     */
    public function toAgentPayload(): array
    {
        return [
            'id' => $this->uuid,
            'type' => $this->type->value,
            'mode' => $this->restriction_mode?->value,
            'message' => $this->message_to_users,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'issued_at' => $this->created_at->toIso8601String(),
        ];
    }
}
