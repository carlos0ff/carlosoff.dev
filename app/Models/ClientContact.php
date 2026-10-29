<?php

namespace App\Models;

use App\Enums\ContactRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'role', 'position', 'email', 'phone', 'document', 'is_primary'])]
class ClientContact extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => ContactRole::class, 'is_primary' => 'boolean'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
