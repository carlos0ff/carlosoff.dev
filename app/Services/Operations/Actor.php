<?php

namespace App\Services\Operations;

use App\Models\ApiCredential;
use App\Models\User;

/**
 * Quem (ou qual regra) originou uma ação — gravado em comandos e auditoria.
 */
final class Actor
{
    public function __construct(public string $type, public ?int $id, public string $label, public bool $isHumanApproval) {}

    public static function user(User $user): self
    {
        return new self('user', $user->id, $user->name, true);
    }

    public static function credential(ApiCredential $credential): self
    {
        return new self('api', $credential->id, "API: {$credential->name} ({$credential->key_id})", $credential->user?->isStaff() === true);
    }

    public static function rule(string $rule): self
    {
        return new self('rule', null, "Regra: {$rule}", false);
    }
}
