<?php

namespace Tests\Feature\Gestao;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\ScopeRequestStatus;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Document;
use App\Models\User;
use App\Services\Contracts\DocumentService;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureProviderException;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeSignatureProvider;
use Tests\TestCase;

class DocumentSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected Contract $contract;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond();
        Notification::fake();
        Storage::fake('local');
        FakeSignatureProvider::$calls = [];
        app(SignatureManager::class)->extend('fake', FakeSignatureProvider::class);
        config(['gestao.signature.driver' => 'fake', 'gestao.company.email' => 'contato@empresa.test']);

        $this->seed(ContractTemplateSeeder::class);
        $this->admin = User::factory()->admin()->create();
        $this->contract = Contract::factory()->create(['scope' => 'Cadastro & relatórios <b>especiais</b>']);
        $this->contract->project->client->contacts()->create(['name' => 'Maria Responsável', 'role' => 'contract', 'email' => 'maria@cliente.test', 'is_primary' => true]);
    }

    protected function generate(DocumentType $type = DocumentType::DevelopmentContract): Document
    {
        $this->actingAs($this->admin)->post("/admin/gestao/projetos/{$this->contract->project->uuid}/documentos", ['type' => $type->value])->assertRedirect();

        return Document::latest('id')->first();
    }

    protected function webhook(Document $document, string $event, ?string $email = null, ?string $secret = null): TestResponse
    {
        $body = json_encode(['event' => $event, 'envelope_id' => $document->provider_envelope_id, 'signer_email' => $email, 'occurred_at' => now()->toIso8601String(), 'signed_file_url' => 'https://provedor.test/assinado.pdf']);

        return $this->call('POST', '/api/webhooks/assinatura/fake', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_HMAC' => 'sha256='.hash_hmac('sha256', $body, $secret ?? FakeSignatureProvider::$secret),
        ], $body);
    }

    protected function approveTemplate(): void
    {
        ContractTemplate::query()->update(['legal_reviewed_at' => now(), 'legal_reviewed_by' => $this->admin->id, 'legal_review_notes' => 'Revisado']);
    }

    public function test_document_is_generated_from_template_with_escaped_variables_and_default_signers(): void
    {
        $document = $this->generate();

        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertStringContainsString($this->contract->number, $document->body_html);
        $this->assertStringContainsString('Cadastro &amp; relatórios &lt;b&gt;especiais&lt;/b&gt;', $document->body_html);
        $this->assertStringContainsString('[preencher: contrato.clausula.warranty]', $document->body_html);
        $this->assertEqualsCanonicalizing(['maria@cliente.test', 'contato@empresa.test'], $document->signers->pluck('email')->all());
    }

    public function test_unreviewed_template_and_missing_fields_block_locking(): void
    {
        $document = $this->generate();
        app(DocumentService::class)->submitForReview($document);

        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/congelar")->assertSessionHasErrors('document');
        $this->assertNull($document->refresh()->locked_at);

        $this->approveTemplate();
        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/congelar")->assertSessionHasErrors('document');
        $this->assertStringContainsString('não preenchidos', session('errors')->first('document'));
    }

    public function test_full_signature_flow_with_hash_webhook_evidence_and_contract_activation(): void
    {
        $this->approveTemplate();
        $document = $this->generate();
        $this->actingAs($this->admin)->put("/admin/gestao/documentos/{$document->uuid}", ['body_html' => '<h1>Contrato</h1><p>Texto revisado</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="x()">x</a>'])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('<script', $document->refresh()->body_html);
        $this->assertStringNotContainsString('onclick', $document->body_html);
        $this->assertStringNotContainsString('javascript:', $document->body_html);

        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/revisao");
        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/congelar")->assertSessionHasNoErrors();

        $document->refresh();
        $pdf = Storage::disk('local')->get($document->file_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(hash('sha256', $pdf), $document->file_sha256);

        // Congelado: não aceita mais edição.
        $this->actingAs($this->admin)->put("/admin/gestao/documentos/{$document->uuid}", ['body_html' => '<p>mudança</p>'])->assertSessionHasErrors('document');

        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/enviar")->assertSessionHasNoErrors();
        $document->refresh();
        $this->assertSame(DocumentStatus::AwaitingSignature, $document->status);
        $this->assertSame('env-'.$document->id, $document->provider_envelope_id);
        $this->assertSame($document->file_sha256, FakeSignatureProvider::$calls[0]['sha256'], 'O arquivo enviado é exatamente o congelado.');
        $this->assertSame('awaiting_signature', $this->contract->refresh()->status->value);

        // Webhook com HMAC inválido: registrado, mas não altera nada.
        $this->webhook($document, 'sign', 'maria@cliente.test', 'segredo-falso')->assertUnauthorized();
        $this->assertSame(DocumentStatus::AwaitingSignature, $document->refresh()->status);
        $this->assertDatabaseHas('signature_events', ['document_id' => $document->id, 'signature_valid' => false]);

        $this->webhook($document, 'sign', 'maria@cliente.test')->assertOk();
        $this->webhook($document, 'sign', 'maria@cliente.test')->assertOk()->assertJsonPath('status', 'duplicate');
        $document->refresh();
        $this->assertSame(DocumentStatus::PartiallySigned, $document->status);
        $maria = $document->signers()->where('email', 'maria@cliente.test')->first();
        $this->assertSame('signed', $maria->status->value);
        $this->assertSame('203.0.113.10', $maria->evidence['ip']);

        $this->webhook($document, 'auto_close')->assertOk();
        $document->refresh();
        $this->assertSame(DocumentStatus::Signed, $document->status);
        $this->assertNotNull($document->signed_file_path);
        $this->assertSame(hash('sha256', '%PDF-1.4 arquivo assinado de teste'), $document->signed_file_sha256);
        $this->assertSame('active', $this->contract->refresh()->status->value);
        $this->assertSame('active', $this->contract->project->refresh()->contract_status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SIGN', 'resource_id' => $document->id]);
    }

    public function test_refusal_and_expiration_are_final(): void
    {
        $this->approveTemplate();
        foreach (['refusal' => DocumentStatus::Refused, 'deadline' => DocumentStatus::Expired] as $event => $status) {
            $document = $this->generate();
            $document->forceFill(['status' => DocumentStatus::AwaitingSignature, 'provider' => 'fake', 'provider_envelope_id' => 'env-x-'.$event, 'locked_at' => now()])->save();

            $this->webhook($document, $event, 'maria@cliente.test')->assertOk();
            $this->assertSame($status, $document->refresh()->status);

            $this->webhook($document, 'auto_close')->assertOk();
            $this->assertSame($status, $document->refresh()->status, 'Evento posterior não "ressuscita" documento finalizado.');
        }
    }

    public function test_signed_scope_term_counts_as_client_consent(): void
    {
        $this->approveTemplate();
        $scope = $this->contract->project->scopeRequests()->create(['title' => 'Integração', 'description' => 'API de frete', 'amount_cents' => 150000, 'estimated_hours' => 10, 'estimated_days' => 5, 'acceptance_criteria' => 'Cotação em tempo real', 'impact_analysis' => 'Baixo']);
        $scope->forceFill(['status' => ScopeRequestStatus::Quoted])->save();

        $document = app(DocumentService::class)->generate($this->contract->project, DocumentType::ScopeChange, $this->admin, scope: $scope);
        $document->forceFill(['status' => DocumentStatus::AwaitingSignature, 'provider' => 'fake', 'provider_envelope_id' => 'env-scope', 'locked_at' => now(), 'file_sha256' => str_repeat('a', 64)])->save();

        $this->webhook($document, 'auto_close')->assertOk();

        $this->assertSame(ScopeRequestStatus::Approved, $scope->refresh()->status);
        $this->assertNotNull($scope->decided_at);
    }

    public function test_send_is_blocked_without_provider_credentials(): void
    {
        config(['gestao.signature.driver' => 'clicksign', 'gestao.signature.clicksign.access_token' => null]);
        $this->approveTemplate();
        $document = $this->generate();
        $document->forceFill(['status' => DocumentStatus::UnderReview, 'locked_at' => now(), 'file_path' => 'x.pdf', 'file_sha256' => str_repeat('a', 64)])->save();

        $this->actingAs($this->admin)->post("/admin/gestao/documentos/{$document->uuid}/enviar")->assertSessionHasErrors('document');
        $this->assertNull($document->refresh()->provider_envelope_id);
    }

    public function test_tampered_pdf_is_not_sent(): void
    {
        $this->approveTemplate();
        $document = $this->generate();
        $document->forceFill(['status' => DocumentStatus::UnderReview, 'body_html' => '<p>ok</p>'])->save();
        app(DocumentService::class)->lock($document, $this->admin);
        Storage::disk('local')->put($document->refresh()->file_path, '%PDF adulterado');

        $this->expectException(SignatureProviderException::class);
        app(DocumentService::class)->performSend($document);
    }
}
