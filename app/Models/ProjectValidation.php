<?php

namespace App\Models;

use App\Enums\ValidationOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de validação de uma versão. Fica "pendente" até o cliente responder — sem aceite tácito.
 */
#[Fillable(['client_project_id', 'version', 'environment', 'environment_url', 'features_evaluated', 'tests_performed', 'known_issues', 'evidence', 'created_by'])]
class ProjectValidation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['outcome' => ValidationOutcome::class, 'responded_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
