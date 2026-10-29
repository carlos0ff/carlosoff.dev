<?php

namespace App\Services\Api;

use App\Enums\AuditAction;
use App\Enums\CredentialKind;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use App\Models\ClientProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Emissão, rotação e revogação de credenciais. O segredo em texto puro só existe no retorno de issue/rotate.
 */
class CredentialService
{
    /**
     * @param  list<string>|null  $abilities
     * @return array{credential: ApiCredential, secret: string}
     */
    public function issueForInstallation(ClientProject $project, string $name, ?User $by = null, ?array $abilities = null): array
    {
        $abilities ??= ApiCredential::INSTALLATION_ABILITIES;

        // Instalação nunca recebe permissão de emitir comandos: o cliente não pode se autoadministrar.
        if (in_array('commands:issue', $abilities, true)) {
            throw new InvalidArgumentException('Credenciais de instalação não podem emitir comandos.');
        }

        return $this->create(CredentialKind::Installation, $name, $abilities, $project, null, $by);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{credential: ApiCredential, secret: string}
     */
    public function issueForAutomation(User $owner, string $name, array $abilities, ?User $by = null): array
    {
        if (! $owner->isStaff()) {
            throw new InvalidArgumentException('Credencial de automação só pode pertencer à equipe.');
        }

        return $this->create(CredentialKind::Automation, $name, array_values(array_intersect($abilities, array_keys(ApiCredential::ABILITIES))), null, $owner, $by);
    }

    /**
     * Gera uma nova credencial; a antiga continua válida durante a janela de transição e depois expira.
     *
     * @return array{credential: ApiCredential, secret: string}
     */
    public function rotate(ApiCredential $old, ?User $by = null): array
    {
        if (! $old->isUsable()) {
            throw new InvalidArgumentException('Credencial revogada ou expirada não pode ser rotacionada.');
        }

        return DB::transaction(function () use ($old, $by) {
            $new = $this->create($old->kind, $old->name, $old->abilities, $old->project, $old->user, $by, $old);

            $graceEnds = now()->addHours((int) config('gestao.api.rotation_grace_hours'));
            $old->forceFill(['expires_at' => $old->expires_at && $old->expires_at->lt($graceEnds) ? $old->expires_at : $graceEnds])->save();

            AuditLog::record(AuditAction::Rotate, 'Credencial API', $old->id, $old->key_id, "Nova: {$new['credential']->key_id}", $this->scope($old));

            return $new;
        });
    }

    public function revoke(ApiCredential $credential, string $reason): void
    {
        $credential->forceFill(['revoked_at' => now(), 'revoked_reason' => $reason])->save();
        AuditLog::record(AuditAction::Revoke, 'Credencial API', $credential->id, $credential->key_id, $reason, $this->scope($credential));
    }

    /**
     * @param  list<string>  $abilities
     * @return array{credential: ApiCredential, secret: string}
     */
    protected function create(CredentialKind $kind, string $name, array $abilities, ?ClientProject $project, ?User $owner, ?User $by, ?ApiCredential $from = null): array
    {
        $secret = Str::random(64);

        $credential = ApiCredential::create([
            'kind' => $kind,
            'name' => $name,
            'key_id' => 'cg_'.Str::lower(Str::random(24)),
            'secret' => $secret,
            'client_project_id' => $project?->id,
            'user_id' => $owner?->id,
            'abilities' => array_values($abilities),
            'rotated_from_id' => $from?->id,
            'created_by' => $by?->id,
        ]);

        AuditLog::record(AuditAction::Create, 'Credencial API', $credential->id, $credential->key_id, "{$kind->label()}: {$name}", $this->scope($credential));

        return ['credential' => $credential, 'secret' => $secret];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scope(ApiCredential $credential): array
    {
        return array_filter(['client_id' => $credential->project?->client_id, 'client_project_id' => $credential->client_project_id]);
    }
}
