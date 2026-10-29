<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'user_name', 'actor_type', 'action', 'resource_type', 'resource_id', 'resource_label', 'details',
    'client_id', 'client_project_id', 'context', 'visible_to_client', 'ip_address', 'user_agent',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'action' => AuditAction::class, 'context' => 'array', 'visible_to_client' => 'boolean'];
    }

    /**
     * Registro de auditoria append-only.
     *
     * @param  array{client_id?: int|null, client_project_id?: int|null, context?: array<string, mixed>, visible_to_client?: bool, actor_type?: string, actor_name?: string}  $scope
     */
    public static function record(AuditAction $action, string $resourceType, ?int $resourceId = null, ?string $resourceLabel = null, ?string $details = null, array $scope = []): self
    {
        $user = Auth::user();
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return static::create([
            'user_id' => $user?->id,
            'user_name' => $scope['actor_name'] ?? $user?->name ?? 'Sistema',
            'actor_type' => $scope['actor_type'] ?? ($user ? 'user' : 'system'),
            'action' => $action->value,
            'resource_type' => Str::limit($resourceType, 40, ''),
            'resource_id' => $resourceId,
            'resource_label' => $resourceLabel === null ? null : Str::limit($resourceLabel, 250),
            'details' => $details === null ? null : Str::limit($details, 250),
            'client_id' => $scope['client_id'] ?? null,
            'client_project_id' => $scope['client_project_id'] ?? null,
            'context' => $scope['context'] ?? null,
            'visible_to_client' => $scope['visible_to_client'] ?? false,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }

    /**
     * Atalho para eventos ligados a um projeto de cliente.
     *
     * @param  array<string, mixed>  $context
     */
    public static function forProject(ClientProject $project, AuditAction $action, string $resourceType, ?Model $resource, string $label, ?string $details = null, array $context = [], bool $visibleToClient = false, ?string $actorName = null, ?string $actorType = null): self
    {
        return static::record($action, $resourceType, $resource?->getKey(), $label, $details, array_filter([
            'client_id' => $project->client_id,
            'client_project_id' => $project->id,
            'context' => $context ?: null,
            'visible_to_client' => $visibleToClient,
            'actor_name' => $actorName,
            'actor_type' => $actorType,
        ], fn ($value) => $value !== null));
    }

    protected static function booted(): void
    {
        // Trilha de auditoria imutável.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
