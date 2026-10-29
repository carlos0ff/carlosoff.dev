<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'document_type', 'body', 'is_active'])]
class ContractTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['document_type' => DocumentType::class, 'legal_reviewed_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_reviewed_by');
    }

    public function isLegallyReviewed(): bool
    {
        return $this->legal_reviewed_at !== null;
    }
}
