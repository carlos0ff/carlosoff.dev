<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de domínio. Titularidade (registrant) é independente de quem administra (managed_by).
 */
#[Fillable([
    'client_id', 'client_project_id', 'name', 'registrar', 'registrant_name', 'registrant_document', 'managed_by', 'payer',
    'registered_at', 'expires_at', 'auto_renew', 'ssl_expires_at', 'ssl_issuer', 'notes',
])]
class Domain extends Model
{
    public const PARTIES = ['client' => 'Cliente', 'us' => 'Nossa empresa', 'third_party' => 'Terceiro'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
            'expires_at' => 'date',
            'ssl_expires_at' => 'date',
            'expires_checked_at' => 'datetime',
            'auto_renew' => 'boolean',
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
}
