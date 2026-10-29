<?php

namespace App\Models;

use App\Enums\SignerRole;
use App\Enums\SignerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'email', 'document_number', 'role', 'auth_method', 'sign_order'])]
class DocumentSigner extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => SignerRole::class, 'status' => SignerStatus::class, 'signed_at' => 'datetime', 'evidence' => 'array'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
