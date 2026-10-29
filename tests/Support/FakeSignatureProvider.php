<?php

namespace Tests\Support;

use App\Models\Document;
use App\Services\Signature\SignatureProvider;

/**
 * Provedor de assinatura SOMENTE para testes automatizados (nunca registrado fora dos testes).
 * Registra as chamadas para as asserções; não produz assinatura alguma.
 */
class FakeSignatureProvider implements SignatureProvider
{
    /** @var list<array{method: string, document: string}> */
    public static array $calls = [];

    public static string $secret = 'segredo-webhook-teste';

    public function name(): string
    {
        return 'fake';
    }

    public function send(Document $document, string $pdfContents): array
    {
        self::$calls[] = ['method' => 'send', 'document' => $document->uuid, 'sha256' => hash('sha256', $pdfContents)];

        return [
            'envelope_id' => 'env-'.$document->id,
            'document_id' => 'doc-'.$document->id,
            'signers' => $document->signers->mapWithKeys(fn ($signer) => [$signer->email => ['id' => 'signer-'.$signer->id, 'url' => null]])->all(),
        ];
    }

    public function fetch(Document $document): array
    {
        self::$calls[] = ['method' => 'fetch', 'document' => $document->uuid];

        return ['status' => 'running', 'signed' => [], 'refused' => [], 'signed_file_url' => null];
    }

    public function cancel(Document $document): void
    {
        self::$calls[] = ['method' => 'cancel', 'document' => $document->uuid];
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader): bool
    {
        return $signatureHeader === 'sha256='.hash_hmac('sha256', $rawBody, self::$secret);
    }

    public function parseWebhook(array $payload): array
    {
        return [
            'event' => $payload['event'],
            'document_id' => $payload['document_id'] ?? null,
            'envelope_id' => $payload['envelope_id'] ?? null,
            'signer_email' => $payload['signer_email'] ?? null,
            'signer_id' => null,
            'occurred_at' => $payload['occurred_at'] ?? null,
            'signed_file_url' => $payload['signed_file_url'] ?? null,
            'evidence' => ['ip' => '203.0.113.10', 'auth' => 'email'],
        ];
    }

    public function download(string $url): string
    {
        return '%PDF-1.4 arquivo assinado de teste';
    }
}
