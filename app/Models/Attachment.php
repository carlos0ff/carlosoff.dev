<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Arquivo privado (disco `local`). Download sempre por rota autenticada, nunca por URL pública.
 */
#[Fillable(['label', 'path', 'original_name', 'mime', 'size', 'sha256', 'visible_to_client', 'uploaded_by'])]
class Attachment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['visible_to_client' => 'boolean', 'size' => 'integer'];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
