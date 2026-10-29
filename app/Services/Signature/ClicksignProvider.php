<?php

namespace App\Services\Signature;

use App\Models\Document;
use App\Models\DocumentSigner;
use App\Support\BrazilianDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Clicksign API v3 (envelopes, JSON:API).
 *
 * Fluxo: cria envelope → envia documento (base64) → cria signatários → requisitos de qualificação (assinar)
 * e autenticação (e-mail) por signatário → ativa o envelope (status running). Webhook: header `Content-Hmac:
 * sha256=<hex>` = HMAC-SHA256(corpo bruto, segredo do webhook).
 *
 * Referência: https://developers.clicksign.com (validar no sandbox antes de produção).
 */
class ClicksignProvider implements SignatureProvider
{
    public function name(): string
    {
        return 'clicksign';
    }

    public function send(Document $document, string $pdfContents): array
    {
        $deadline = now()->addDays((int) config('gestao.signature.clicksign.deadline_days'));

        $envelope = $this->call('post', '/envelopes', ['data' => ['type' => 'envelopes', 'attributes' => [
            'name' => mb_substr($document->title, 0, 250),
            'locale' => 'pt-BR',
            'auto_close' => true,
            'remind_interval' => 3,
            'block_after_refusal' => true,
            'deadline_at' => $deadline->toIso8601String(),
        ]]]);
        $envelopeId = (string) Arr::get($envelope, 'data.id');

        $uploaded = $this->call('post', "/envelopes/{$envelopeId}/documents", ['data' => ['type' => 'documents', 'attributes' => [
            'filename' => "{$document->uuid}.pdf",
            'content_base64' => 'data:application/pdf;base64,'.base64_encode($pdfContents),
        ]]]);
        $documentId = (string) Arr::get($uploaded, 'data.id');

        $signers = [];
        foreach ($document->signers as $signer) {
            /** @var DocumentSigner $signer */
            $created = $this->call('post', "/envelopes/{$envelopeId}/signers", ['data' => ['type' => 'signers', 'attributes' => array_filter([
                'name' => $signer->name,
                'email' => $signer->email,
                'documentation' => $signer->document_number ? BrazilianDocument::format($signer->document_number) : null,
                'has_documentation' => (bool) $signer->document_number,
                'refusable' => true,
                'group' => $signer->sign_order,
                'communicate_events' => ['signature_request' => 'email', 'signature_reminder' => 'email', 'document_signed' => 'email'],
            ], fn ($value) => $value !== null)]]);
            $signerId = (string) Arr::get($created, 'data.id');

            $relationships = [
                'document' => ['data' => ['type' => 'documents', 'id' => $documentId]],
                'signer' => ['data' => ['type' => 'signers', 'id' => $signerId]],
            ];
            $this->call('post', "/envelopes/{$envelopeId}/requirements", ['data' => ['type' => 'requirements',
                'attributes' => ['action' => 'agree', 'role' => match ($signer->role->value) {
                    'contractor' => 'contractor',
                    'client' => 'party',
                    default => 'sign',
                }],
                'relationships' => $relationships]]);
            $this->call('post', "/envelopes/{$envelopeId}/requirements", ['data' => ['type' => 'requirements',
                'attributes' => ['action' => 'provide_evidence', 'auth' => $signer->auth_method ?: 'email'],
                'relationships' => $relationships]]);

            $signers[$signer->email] = ['id' => $signerId, 'url' => null];
        }

        $this->call('patch', "/envelopes/{$envelopeId}", ['data' => ['id' => $envelopeId, 'type' => 'envelopes', 'attributes' => ['status' => 'running']]]);

        return ['envelope_id' => $envelopeId, 'document_id' => $documentId, 'signers' => $signers];
    }

    public function fetch(Document $document): array
    {
        $envelope = $this->call('get', "/envelopes/{$document->provider_envelope_id}");
        $signers = $this->call('get', "/envelopes/{$document->provider_envelope_id}/signers");
        $file = $this->call('get', "/envelopes/{$document->provider_envelope_id}/documents/{$document->provider_document_id}");

        $signed = [];
        $refused = [];
        foreach (Arr::get($signers, 'data', []) as $signer) {
            $email = Arr::get($signer, 'attributes.email');
            $status = Arr::get($signer, 'attributes.status');
            if (in_array($status, ['signed', 'concluded'], true) || Arr::get($signer, 'attributes.signed_at')) {
                $signed[] = $email;
            } elseif (in_array($status, ['refused', 'rejected'], true)) {
                $refused[] = $email;
            }
        }

        return [
            'status' => Arr::get($envelope, 'data.attributes.status'),
            'signed' => array_values(array_filter($signed)),
            'refused' => array_values(array_filter($refused)),
            'signed_file_url' => Arr::get($file, 'data.links.files.signed') ?? Arr::get($file, 'data.links.files.ziped'),
        ];
    }

    public function cancel(Document $document): void
    {
        $this->call('patch', "/envelopes/{$document->provider_envelope_id}", ['data' => ['id' => $document->provider_envelope_id, 'type' => 'envelopes', 'attributes' => ['status' => 'canceled']]]);
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = (string) config('gestao.signature.clicksign.webhook_secret');

        if ($secret === '' || ! $signatureHeader || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), substr($signatureHeader, 7));
    }

    public function parseWebhook(array $payload): array
    {
        $signer = Arr::get($payload, 'event.data.signer', []);

        return [
            'event' => (string) Arr::get($payload, 'event.name', 'unknown'),
            'document_id' => Arr::get($payload, 'document.key'),
            'envelope_id' => Arr::get($payload, 'document.envelope_key') ?? Arr::get($payload, 'envelope.key'),
            'signer_email' => Arr::get($signer, 'email'),
            'signer_id' => Arr::get($signer, 'key'),
            'occurred_at' => Arr::get($payload, 'event.occurred_at'),
            'signed_file_url' => Arr::get($payload, 'document.downloads.signed_file_url'),
            // Evidências relevantes do signatário (sem imagens/hashes de biometria).
            'evidence' => array_filter(Arr::only($signer, ['email', 'name', 'documentation', 'auths', 'address', 'validation', 'communicate_by', 'sign_as']) + [
                'occurred_at' => Arr::get($payload, 'event.occurred_at'),
            ], fn ($value) => $value !== null),
        ];
    }

    public function download(string $url): string
    {
        try {
            $response = Http::timeout((int) config('gestao.signature.clicksign.timeout_seconds'))->retry(2, 500, throw: false)->get($url);
        } catch (ConnectionException $exception) {
            throw new SignatureProviderException('Falha ao baixar arquivo do provedor: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new SignatureProviderException("Falha ao baixar arquivo do provedor (HTTP {$response->status()}).");
        }

        return $response->body();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function call(string $method, string $path, array $payload = []): array
    {
        $token = config('gestao.signature.clicksign.access_token');
        if (blank($token)) {
            throw new SignatureProviderException('CLICKSIGN_ACCESS_TOKEN não configurado.');
        }

        try {
            /** @var Response $response */
            $response = $this->client($token)->{$method}($path, $payload ?: null);
        } catch (ConnectionException $exception) {
            throw new SignatureProviderException('Clicksign indisponível: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            $detail = collect($response->json('errors', []))->map(fn ($error) => $error['detail'] ?? $error['title'] ?? '')->filter()->implode('; ');
            throw new SignatureProviderException("Clicksign respondeu HTTP {$response->status()} em {$method} {$path}".($detail ? ": {$detail}" : ''));
        }

        return $response->json() ?? [];
    }

    protected function client(string $token): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('gestao.signature.clicksign.base_url'), '/'))
            ->timeout((int) config('gestao.signature.clicksign.timeout_seconds'))
            ->connectTimeout(10)
            ->withHeaders(['Authorization' => $token, 'Accept' => 'application/vnd.api+json'])
            ->contentType('application/vnd.api+json');
    }
}
