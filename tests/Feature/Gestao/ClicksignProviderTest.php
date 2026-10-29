<?php

namespace Tests\Feature\Gestao;

use App\Enums\DocumentType;
use App\Models\ClientProject;
use App\Models\Document;
use App\Services\Contracts\DocumentService;
use App\Services\Signature\ClicksignProvider;
use App\Services\Signature\SignatureProviderException;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Valida o formato das chamadas à API v3 da Clicksign (sem rede). O fluxo real precisa ser
 * confirmado no sandbox com `php artisan gestao:clicksign-check` e um envio de teste.
 */
class ClicksignProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gestao.signature.clicksign.access_token' => 'token-sandbox', 'gestao.signature.clicksign.webhook_secret' => 'segredo', 'gestao.signature.clicksign.base_url' => 'https://sandbox.clicksign.com/api/v3', 'gestao.company.email' => null]);
    }

    protected function document(): Document
    {
        $this->seed(ContractTemplateSeeder::class);
        $project = ClientProject::factory()->create();
        $document = app(DocumentService::class)->generate($project, DocumentType::DevelopmentContract, null);
        $document->signers()->create(['name' => 'Maria', 'email' => 'maria@cliente.test', 'document_number' => '52998224725', 'role' => 'client']);
        $document->signers()->create(['name' => 'Empresa', 'email' => 'contato@empresa.test', 'role' => 'contractor']);

        return $document->load('signers');
    }

    public function test_send_creates_envelope_document_signers_requirements_and_activates(): void
    {
        $id = 0;
        Http::fake(function (Request $request) use (&$id) {
            return Http::response(['data' => ['id' => 'id-'.(++$id), 'type' => 'x']], 201);
        });

        $result = (new ClicksignProvider)->send($this->document(), '%PDF-1.4 teste');

        $this->assertSame('id-1', $result['envelope_id']);
        $this->assertSame('id-2', $result['document_id']);
        $this->assertSame(['maria@cliente.test', 'contato@empresa.test'], array_keys($result['signers']));

        $recorded = Http::recorded();
        $paths = $recorded->map(fn ($pair) => $pair[0]->method().' '.parse_url($pair[0]->url(), PHP_URL_PATH))->all();
        $this->assertSame([
            'POST /api/v3/envelopes',
            'POST /api/v3/envelopes/id-1/documents',
            'POST /api/v3/envelopes/id-1/signers',
            'POST /api/v3/envelopes/id-1/requirements',
            'POST /api/v3/envelopes/id-1/requirements',
            'POST /api/v3/envelopes/id-1/signers',
            'POST /api/v3/envelopes/id-1/requirements',
            'POST /api/v3/envelopes/id-1/requirements',
            'PATCH /api/v3/envelopes/id-1',
        ], $paths);

        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'token-sandbox') && str_contains($r->header('Content-Type')[0], 'application/vnd.api+json'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/documents') && str_starts_with($r['data']['attributes']['content_base64'], 'data:application/pdf;base64,'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/requirements') && ($r['data']['attributes']['role'] ?? null) === 'party');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/requirements') && ($r['data']['attributes']['auth'] ?? null) === 'email');
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['data']['attributes']['status'] === 'running');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/signers') && ($r['data']['attributes']['documentation'] ?? null) === '529.982.247-25');
    }

    public function test_api_errors_become_provider_exceptions(): void
    {
        Http::fake(['*' => Http::response(['errors' => [['detail' => 'token inválido']]], 401)]);

        $this->expectException(SignatureProviderException::class);
        $this->expectExceptionMessage('token inválido');
        (new ClicksignProvider)->send($this->document(), '%PDF');
    }

    public function test_webhook_hmac_matches_content_hmac_header(): void
    {
        $body = '{"event":{"name":"sign"}}';
        $provider = new ClicksignProvider;

        $this->assertTrue($provider->verifyWebhook($body, 'sha256='.hash_hmac('sha256', $body, 'segredo')));
        $this->assertFalse($provider->verifyWebhook($body, 'sha256='.hash_hmac('sha256', $body, 'outro')));
        $this->assertFalse($provider->verifyWebhook($body.' ', 'sha256='.hash_hmac('sha256', $body, 'segredo')));
        $this->assertFalse($provider->verifyWebhook($body, null));

        config(['gestao.signature.clicksign.webhook_secret' => null]);
        $this->assertFalse($provider->verifyWebhook($body, 'sha256='.hash_hmac('sha256', $body, '')), 'Sem segredo configurado, nenhum webhook é aceito.');
    }

    public function test_webhook_payload_is_normalized_without_biometric_data(): void
    {
        $parsed = (new ClicksignProvider)->parseWebhook([
            'event' => ['name' => 'sign', 'occurred_at' => '2026-10-14T10:00:00-03:00', 'data' => ['signer' => ['key' => 'k1', 'email' => 'maria@cliente.test', 'address' => '10.0.0.1', 'auths' => ['email'], 'selfie_hash' => 'abc', 'latitude' => '-23']]],
            'document' => ['key' => 'doc-1', 'downloads' => ['signed_file_url' => 'https://x/signed.pdf']],
        ]);

        $this->assertSame('sign', $parsed['event']);
        $this->assertSame('doc-1', $parsed['document_id']);
        $this->assertSame('maria@cliente.test', $parsed['signer_email']);
        $this->assertSame('https://x/signed.pdf', $parsed['signed_file_url']);
        $this->assertArrayNotHasKey('selfie_hash', $parsed['evidence']);
        $this->assertArrayNotHasKey('latitude', $parsed['evidence']);
    }
}
