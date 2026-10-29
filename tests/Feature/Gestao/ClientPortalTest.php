<?php

namespace Tests\Feature\Gestao;

use App\Enums\DocumentStatus;
use App\Enums\ScopeRequestStatus;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\Document;
use App\Models\ProjectValidation;
use App\Models\ScopeRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Gestao\ClientDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected ClientProject $mine;

    protected ClientProject $theirs;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');

        $this->mine = ClientProject::factory()->create(['name' => 'Meu Sistema']);
        $this->theirs = ClientProject::factory()->create(['name' => 'Sistema Alheio']);
        $this->user = User::factory()->portal($this->mine->client)->create();
    }

    protected function document(ClientProject $project, DocumentStatus $status): Document
    {
        $document = Document::create(['client_id' => $project->client_id, 'client_project_id' => $project->id, 'type' => 'development_contract', 'title' => 'Contrato '.$project->name, 'body_html' => '<p>x</p>', 'visible_to_client' => true]);
        $document->forceFill(['status' => $status])->save();

        return $document;
    }

    public function test_client_sees_only_own_projects_and_never_internal_notes(): void
    {
        $this->actingAs($this->user)->get('/portal')->assertOk()->assertSee('Meu Sistema')->assertDontSee('Sistema Alheio');

        $this->actingAs($this->user)->get("/portal/projetos/{$this->mine->uuid}")->assertOk()
            ->assertDontSee('Anotação interna do projeto')
            ->assertDontSee('Nota interna confidencial');

        $this->actingAs($this->user)->get("/portal/projetos/{$this->theirs->uuid}")->assertNotFound();
    }

    public function test_client_cannot_reach_other_clients_resources_by_id(): void
    {
        $doc = $this->document($this->theirs, DocumentStatus::Signed);
        $charge = Charge::factory()->create(['client_project_id' => $this->theirs->id]);
        $ticket = SupportTicket::create(['client_id' => $this->theirs->client_id, 'client_project_id' => $this->theirs->id, 'subject' => 'x', 'description' => 'y', 'priority' => 'low']);
        $validation = ProjectValidation::create(['client_project_id' => $this->theirs->id, 'version' => '1.0', 'features_evaluated' => 'x']);
        $scope = ScopeRequest::create(['client_project_id' => $this->theirs->id, 'title' => 'x', 'description' => 'y']);
        $scope->forceFill(['status' => ScopeRequestStatus::Quoted])->save();

        $this->actingAs($this->user)->get("/portal/documentos/{$doc->uuid}")->assertNotFound();
        $this->actingAs($this->user)->get("/portal/chamados/{$ticket->id}")->assertNotFound();
        $this->actingAs($this->user)->post("/portal/cobrancas/{$charge->uuid}/contestar", ['reason' => 'tentativa de contestar alheia'])->assertNotFound();
        $this->actingAs($this->user)->post("/portal/validacoes/{$validation->id}", ['outcome' => 'approved', 'confirm' => 1])->assertNotFound();
        $this->actingAs($this->user)->post("/portal/solicitacoes/{$scope->id}/decisao", ['decision' => 'approve', 'consent' => 1, 'terms_hash' => hash('sha256', $scope->termsForConsent())])->assertNotFound();
        $this->actingAs($this->user)->post("/portal/projetos/{$this->theirs->uuid}/chamados", ['subject' => 'x', 'description' => 'y', 'priority' => 'low'])->assertNotFound();

        $this->assertNull($charge->refresh()->disputed_at);
        $this->assertSame('pending', $validation->refresh()->outcome->value);
        $this->assertSame(ScopeRequestStatus::Quoted, $scope->refresh()->status);
    }

    public function test_drafts_are_hidden_and_signed_pdf_is_downloadable(): void
    {
        $draft = $this->document($this->mine, DocumentStatus::Draft);
        $signed = $this->document($this->mine, DocumentStatus::Signed);
        Storage::disk('local')->put('documents/x/assinado.pdf', '%PDF assinado');
        $signed->forceFill(['signed_file_path' => 'documents/x/assinado.pdf'])->save();

        $this->actingAs($this->user)->get('/portal/documentos')->assertOk()->assertSee($signed->title);
        $this->actingAs($this->user)->get("/portal/documentos/{$draft->uuid}")->assertNotFound();
        $this->actingAs($this->user)->get("/portal/documentos/{$signed->uuid}/pdf/assinado")->assertOk()->assertDownload();
    }

    public function test_validation_requires_explicit_answer_and_records_evidence(): void
    {
        $validation = ProjectValidation::create(['client_project_id' => $this->mine->id, 'version' => '2.0', 'features_evaluated' => 'Login']);

        $this->actingAs($this->user)->post("/portal/validacoes/{$validation->id}", ['outcome' => 'approved_with_reservations', 'confirm' => 1])->assertSessionHasErrors('client_notes');
        $this->actingAs($this->user)->post("/portal/validacoes/{$validation->id}", ['outcome' => 'approved_with_reservations', 'client_notes' => 'Ajustar rodapé', 'confirm' => 1])->assertSessionHasNoErrors();

        $validation->refresh();
        $this->assertSame('approved_with_reservations', $validation->outcome->value);
        $this->assertSame($this->user->id, $validation->responded_by);
        $this->assertNotNull($validation->response_ip);
        $this->actingAs($this->user)->post("/portal/validacoes/{$validation->id}", ['outcome' => 'rejected', 'client_notes' => 'x', 'confirm' => 1])->assertStatus(422);
    }

    public function test_scope_quote_approval_requires_consent_and_current_terms(): void
    {
        $scope = ScopeRequest::create(['client_project_id' => $this->mine->id, 'title' => 'Relatório', 'description' => 'PDF mensal', 'amount_cents' => 80000, 'estimated_hours' => 8, 'estimated_days' => 4, 'acceptance_criteria' => 'Gera PDF']);
        $scope->forceFill(['status' => ScopeRequestStatus::Quoted])->save();
        $hash = hash('sha256', $scope->termsForConsent());

        $this->actingAs($this->user)->post("/portal/solicitacoes/{$scope->id}/decisao", ['decision' => 'approve', 'consent' => 0, 'terms_hash' => $hash])->assertSessionHasErrors('consent');

        // O orçamento mudou depois que a página foi aberta.
        $scope->forceFill(['amount_cents' => 90000])->save();
        $this->actingAs($this->user)->post("/portal/solicitacoes/{$scope->id}/decisao", ['decision' => 'approve', 'consent' => 1, 'terms_hash' => $hash])->assertSessionHasErrors('scope');
        $this->assertSame(ScopeRequestStatus::Quoted, $scope->refresh()->status);

        $this->actingAs($this->user)->post("/portal/solicitacoes/{$scope->id}/decisao", ['decision' => 'approve', 'consent' => 1, 'terms_hash' => hash('sha256', $scope->termsForConsent())])->assertSessionHasNoErrors();
        $scope->refresh();
        $this->assertSame(ScopeRequestStatus::Approved, $scope->status);
        $this->assertSame($this->user->id, $scope->decided_by);
        $this->assertSame(hash('sha256', $scope->termsForConsent()), $scope->decision_terms_hash);
    }

    public function test_staff_cannot_mark_a_scope_request_as_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $scope = ScopeRequest::create(['client_project_id' => $this->mine->id, 'title' => 'x', 'description' => 'y']);

        $this->actingAs($admin)->post("/admin/gestao/solicitacoes/{$scope->id}/status", ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post("/admin/gestao/solicitacoes/{$scope->id}/status", ['status' => 'delivered'])->assertSessionHasErrors('scope');
    }

    public function test_proof_upload_and_dispute_never_mark_the_charge_as_paid(): void
    {
        $charge = Charge::factory()->create(['client_project_id' => $this->mine->id]);

        $this->actingAs($this->user)->post("/portal/cobrancas/{$charge->uuid}/comprovante", ['file' => UploadedFile::fake()->create('pix.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post("/portal/cobrancas/{$charge->uuid}/contestar", ['reason' => 'Valor diferente do combinado'])->assertSessionHasNoErrors();

        $charge->refresh();
        $this->assertSame('pending', $charge->status->value);
        $this->assertSame(0, $charge->paid_cents);
        $this->assertNotNull($charge->disputed_at);
        $this->assertSame(1, $charge->attachments()->count());
    }

    public function test_ticket_deadlines_come_from_contract_sla(): void
    {
        $contract = Contract::factory()->active()->create(['client_project_id' => $this->mine->id, 'client_id' => $this->mine->client_id, 'support_sla' => ['urgent' => ['response_hours' => 2, 'resolution_hours' => 24]]]);

        $this->actingAs($this->user)->post("/portal/projetos/{$this->mine->uuid}/chamados", ['subject' => 'Fora do ar', 'description' => 'Erro 500', 'priority' => 'urgent'])->assertRedirect();

        $ticket = SupportTicket::sole();
        $this->assertEqualsWithDelta(now()->addHours(2)->timestamp, $ticket->response_due_at->timestamp, 5);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $ticket->resolution_due_at->timestamp, 5);

        // Nota interna da equipe não aparece para o cliente.
        $ticket->messages()->create(['user_id' => User::factory()->admin()->create()->id, 'body' => 'Cliente difícil, cuidado', 'is_internal' => true]);
        $this->actingAs($this->user)->get("/portal/chamados/{$ticket->id}")->assertOk()->assertDontSee('Cliente difícil');
    }

    public function test_portal_invite_lets_the_client_set_a_password_and_log_in(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)->post("/admin/gestao/clientes/{$client->id}/acesso-portal", ['name' => 'Ana', 'email' => 'ana@cliente.test'])->assertSessionHas('portal_link');
        auth()->logout();

        $ana = User::where('email', 'ana@cliente.test')->sole();
        $this->assertFalse($ana->is_admin);
        $token = Password::broker()->createToken($ana);

        $this->post('/portal/definir-senha', ['token' => $token, 'email' => 'ana@cliente.test', 'password' => 'SenhaForte123', 'password_confirmation' => 'SenhaForte123'])->assertRedirect(route('portal.login'));
        $this->post('/portal/login', ['email' => 'ana@cliente.test', 'password' => 'SenhaForte123'])->assertRedirect(route('portal.dashboard'));

        // Usuário do portal não entra no painel administrativo.
        auth()->logout();
        $this->post('/admin/login', ['email' => 'ana@cliente.test', 'password' => 'SenhaForte123'])->assertSessionHasErrors('email');
    }

    public function test_lgpd_export_omits_internal_notes_and_anonymization_respects_open_obligations(): void
    {
        $service = app(ClientDataService::class);
        $client = $this->mine->client;

        $export = json_encode($service->export($client));
        $this->assertStringContainsString($client->email, $export);
        $this->assertStringNotContainsString('Nota interna confidencial', $export);

        $charge = Charge::factory()->create(['client_project_id' => $this->mine->id]);
        $this->assertIsString($service->anonymize($client));

        $charge->forceFill(['status' => 'cancelled'])->save();
        $this->assertTrue($service->anonymize($client->refresh()));
        $client->refresh();
        $this->assertNull($client->document);
        $this->assertStringStartsWith('anonimizado+', $client->email);
        $this->assertSame(1, Charge::where('client_id', $client->id)->count(), 'Registros financeiros são preservados.');
    }
}
