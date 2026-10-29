<?php

namespace App\Services\Signature;

use App\Models\Document;

/**
 * Provedor de assinatura eletrônica. Nenhuma implementação pode "simular" assinatura em produção.
 */
interface SignatureProvider
{
    public function name(): string;

    /**
     * Cria o envelope com o PDF imutável e os signatários, e inicia o fluxo de assinatura.
     *
     * @return array{envelope_id: string, document_id: string, signers: array<string, array{id: string, url: string|null}>}
     *
     * @throws SignatureProviderException
     */
    public function send(Document $document, string $pdfContents): array;

    /**
     * Situação atual no provedor (para sincronizar caso um webhook se perca).
     *
     * @return array{status: string|null, signed: list<string>, refused: list<string>, signed_file_url: string|null}
     *
     * @throws SignatureProviderException
     */
    public function fetch(Document $document): array;

    /**
     * @throws SignatureProviderException
     */
    public function cancel(Document $document): void;

    public function verifyWebhook(string $rawBody, ?string $signatureHeader): bool;

    /**
     * Normaliza o webhook.
     *
     * @param  array<string, mixed>  $payload
     * @return array{event: string, document_id: string|null, envelope_id: string|null, signer_email: string|null, signer_id: string|null, occurred_at: string|null, signed_file_url: string|null, evidence: array<string, mixed>}
     */
    public function parseWebhook(array $payload): array;

    /**
     * @throws SignatureProviderException
     */
    public function download(string $url): string;
}
