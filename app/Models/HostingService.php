<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hospedagem — contratada e cobrada separadamente do domínio e do desenvolvimento.
 */
#[Fillable([
    'client_id', 'client_project_id', 'provider', 'plan', 'resources', 'managed_by', 'payer', 'monthly_cost_cents', 'renews_at',
    'limits_and_responsibilities', 'backup_policy', 'migration_procedure', 'last_backup_confirmed_at',
])]
class HostingService extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['renews_at' => 'date', 'last_backup_confirmed_at' => 'datetime', 'monthly_cost_cents' => 'integer'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
