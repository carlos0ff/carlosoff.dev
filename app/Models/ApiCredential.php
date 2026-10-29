<?php

namespace App\Models;

use App\Enums\CredentialKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credencial HMAC da API. O segredo fica criptografado com a APP_KEY e só é exibido uma vez, na emissão.
 */
#[Fillable(['kind', 'name', 'key_id', 'secret', 'client_project_id', 'user_id', 'abilities', 'rotated_from_id', 'expires_at', 'created_by'])]
#[Hidden(['secret'])]
class ApiCredential extends Model
{
    public const ABILITIES = [
        'status:read' => 'Consultar estado operacional',
        'health:read' => 'Consultar saúde',
        'health:write' => 'Enviar heartbeat/saúde (agente)',
        'billing:read' => 'Consultar situação financeira resumida',
        'commands:pull' => 'Receber e confirmar comandos (agente)',
        'commands:issue' => 'Emitir comandos (somente automação administrativa)',
    ];

    public const INSTALLATION_ABILITIES = ['status:read', 'health:read', 'health:write', 'billing:read', 'commands:pull'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CredentialKind::class,
            'secret' => 'encrypted',
            'abilities' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    /**
     * Instalação: só o próprio projeto. Automação: qualquer projeto (é credencial da equipe).
     */
    public function canAccessProject(ClientProject $project): bool
    {
        return match ($this->kind) {
            CredentialKind::Installation => $this->client_project_id === $project->id,
            CredentialKind::Automation => $this->user?->isStaff() === true,
        };
    }
}
