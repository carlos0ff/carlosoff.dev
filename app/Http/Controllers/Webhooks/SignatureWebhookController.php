<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\SignatureEvent;
use App\Services\Contracts\DocumentService;
use App\Services\Signature\SignatureManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Webhook do provedor de assinatura. Só eventos com HMAC válido alteram documentos; todos ficam registrados.
 */
class SignatureWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, SignatureManager $signatures, DocumentService $documents): JsonResponse
    {
        abort_unless($signatures->has($provider), 404);

        $driver = $signatures->driver($provider);
        $raw = $request->getContent();
        $valid = $driver->verifyWebhook($raw, $request->header('Content-Hmac'));
        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $event = $driver->parseWebhook($payload);
        $document = Document::query()
            ->where('provider', $provider)
            ->where(fn ($query) => $query
                ->when($event['document_id'], fn ($q) => $q->orWhere('provider_document_id', $event['document_id']))
                ->when($event['envelope_id'], fn ($q) => $q->orWhere('provider_envelope_id', $event['envelope_id'])))
            ->when(! $event['document_id'] && ! $event['envelope_id'], fn ($q) => $q->whereRaw('1 = 0'))
            ->first();

        try {
            $record = SignatureEvent::create([
                'document_id' => $document?->id,
                'provider' => $provider,
                'event' => mb_substr($event['event'], 0, 60),
                // Só eventos autênticos participam da deduplicação: uma cópia forjada não pode "ocupar" o hash do evento real.
                'payload_sha256' => $valid ? hash('sha256', $raw) : hash('sha256', $raw.'|invalid|'.Str::uuid()),
                'payload' => $payload,
                'signature_valid' => $valid,
                'occurred_at' => $event['occurred_at'],
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['status' => 'duplicate']);
        }

        if (! $valid) {
            AuditLog::record(AuditAction::Webhook, 'Assinatura', $document?->id, $provider, 'Webhook rejeitado: assinatura HMAC inválida', ['actor_type' => 'webhook', 'actor_name' => $provider]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        if ($document) {
            $documents->applyProviderEvent($document, $event);
            $record->forceFill(['processed_at' => now()])->save();
        }

        return response()->json(['status' => 'ok']);
    }
}
