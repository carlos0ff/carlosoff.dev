<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_project_id', 'source', 'is_up', 'http_status', 'response_time_ms', 'ssl_expires_at', 'deployed_version',
    'last_backup_at', 'app_errors_last_hour', 'metrics', 'error', 'checked_at',
])]
class HealthCheck extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_up' => 'boolean',
            'metrics' => 'array',
            'ssl_expires_at' => 'datetime',
            'last_backup_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
