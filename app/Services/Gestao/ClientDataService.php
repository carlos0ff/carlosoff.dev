<?php

namespace App\Services\Gestao;

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * LGPD: exportação (portabilidade) e anonimização no encerramento.
 * Registros financeiros e documentos assinados são mantidos pelo prazo legal (config gestao.retention).
 */
class ClientDataService
{
    /**
     * @return array<string, mixed>
     */
    public function export(Client $client): array
    {
        $client->load(['contacts', 'projects.domains', 'projects.hostingServices', 'contracts', 'charges.payments', 'documents.signers', 'portalUsers']);

        return [
            'exported_at' => now()->toIso8601String(),
            'client' => $client->makeHidden(['internal_notes'])->toArray(),
            'portal_users' => $client->portalUsers->map->only(['name', 'email', 'created_at', 'last_login_at']),
            'tickets' => SupportTicket::where('client_id', $client->id)->with(['messages' => fn ($q) => $q->where('is_internal', false)])->get()->toArray(),
        ];
    }

    /**
     * @return true|string true quando concluído, ou o motivo do bloqueio
     */
    public function anonymize(Client $client): bool|string
    {
        if ($client->charges()->open()->exists()) {
            return 'Existem cobranças em aberto. Regularize ou cancele antes de anonimizar.';
        }

        if ($client->contracts()->where('status', ContractStatus::Active->value)->exists()) {
            return 'Existem contratos vigentes. Encerre-os antes de anonimizar.';
        }

        DB::transaction(function () use ($client) {
            $client->forceFill([
                'legal_name' => "Cliente anonimizado #{$client->id}",
                'trade_name' => null,
                'document' => null,
                'email' => "anonimizado+{$client->id}@invalid.local",
                'phone' => null,
                'whatsapp' => null,
                'address_street' => null,
                'address_number' => null,
                'address_complement' => null,
                'address_district' => null,
                'address_city' => null,
                'address_state' => null,
                'address_zip' => null,
                'billing_email' => null,
                'billing_notes' => null,
                'internal_notes' => null,
                'status' => ClientStatus::Ended,
                'anonymized_at' => now(),
            ])->save();

            $client->contacts()->delete();

            foreach ($client->portalUsers as $user) {
                $user->forceFill(['name' => 'Usuário anonimizado', 'email' => "anonimizado+u{$user->id}@invalid.local", 'password' => Str::password(64), 'remember_token' => null])->save();
            }
        });

        AuditLog::record(AuditAction::Anonymize, 'Cliente', $client->id, "Cliente #{$client->id}", 'Dados pessoais anonimizados', ['client_id' => $client->id]);

        return true;
    }
}
