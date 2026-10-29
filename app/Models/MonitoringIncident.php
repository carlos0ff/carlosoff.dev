<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Incidente TÉCNICO. Nunca é usado para decidir suspensão, e inadimplência nunca vira incidente.
 */
#[Fillable(['client_project_id', 'kind', 'summary', 'opened_at', 'resolved_at'])]
class MonitoringIncident extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
