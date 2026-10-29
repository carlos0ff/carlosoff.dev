<?php

namespace Tests\Feature\Gestao;

use App\Enums\CommandStatus;
use App\Models\ApiCredential;
use App\Models\BillingPolicy;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\RemoteCommand;
use App\Models\User;
use App\Services\Api\CredentialService;
use App\Services\Api\RequestSigner;
use App\Services\Billing\DunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Support\SignsApiRequests;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase, SignsApiRequests;

    protected ClientProject $project;

    protected ApiCredential $credential;

    protected string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->project = ClientProject::factory()->create();
        ['credential' => $this->credential, 'secret' => $this->secret] = app(CredentialService::class)->issueForInstallation($this->project, 'agente');
    }

    /**
     * @return array{credential: ApiCredential, secret: string}
     */
    protected function automation(): array
    {
        return app(CredentialService::class)->issueForAutomation(User::factory()->admin()->create(), 'script', ['status:read', 'billing:read', 'commands:issue']);
    }

    public function test_installation_reads_its_own_status_and_billing_status_without_amounts(): void
    {
        $this->signedJson('GET', "/api/v1/projects/{$this->project->uuid}/status", $this->credential, $this->secret)
            ->assertOk()
            ->assertJsonPath('data.project.id', $this->project->uuid)
            ->assertJsonPath('data.operational_status', 'active')
            ->assertJsonMissingPath('data.project.internal_notes');

        $this->signedJson('GET', "/api/v1/projects/{$this->project->uuid}/billing-status", $this->credential, $this->secret)
            ->assertOk()
            ->assertJsonPath('data.financial_status', 'up_to_date')
            ->assertJsonMissingPath('data.open_cents');

        $this->assertNotNull($this->credential->refresh()->last_used_at);
    }

    public function test_requests_without_valid_signature_are_rejected(): void
    {
        $uri = "/api/v1/projects/{$this->project->uuid}/status";

        $this->getJson($uri)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->signedJson('GET', $uri, $this->credential, 'segredo-errado')->assertUnauthorized();
        $this->signedJson('GET', $uri, $this->credential, $this->secret, timestamp: time() - 3600)->assertUnauthorized()->assertJsonPath('error.code', 'stale_request');

        // Corpo adulterado: assinatura calculada sobre outro conteúdo.
        $timestamp = (string) time();
        $this->call('POST', "/api/v1/projects/{$this->project->uuid}/health", [], [], [], $this->transformHeadersToServerVars([
            'X-Api-Key' => $this->credential->key_id, 'X-Timestamp' => $timestamp, 'X-Nonce' => str_repeat('a', 32),
            'X-Signature' => RequestSigner::sign($this->secret, 'POST', "/api/v1/projects/{$this->project->uuid}/health", $timestamp, str_repeat('a', 32), '{"state":"active"}'),
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
        ]), '{"state":"suspended"}')->assertUnauthorized();
    }

    public function test_replayed_nonce_is_rejected(): void
    {
        $uri = "/api/v1/projects/{$this->project->uuid}/status";

        $this->signedJson('GET', $uri, $this->credential, $this->secret, nonce: 'nonce-fixo-1234567890')->assertOk();
        $this->signedJson('GET', $uri, $this->credential, $this->secret, nonce: 'nonce-fixo-1234567890')->assertStatus(409)->assertJsonPath('error.code', 'replayed_request');
    }

    public function test_unknown_uuid_requires_authentication_before_revealing_anything(): void
    {
        $this->getJson('/api/v1/projects/00000000-0000-4000-8000-000000000000/status')->assertUnauthorized();
    }

    public function test_installation_cannot_access_another_clients_project(): void
    {
        $other = ClientProject::factory()->create();

        $this->signedJson('GET', "/api/v1/projects/{$other->uuid}/status", $this->credential, $this->secret)->assertNotFound();
        $this->signedJson('GET', "/api/v1/projects/{$other->uuid}/commands/pending", $this->credential, $this->secret)->assertNotFound();
    }

    public function test_installation_cannot_issue_commands_or_change_its_financial_state(): void
    {
        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/reactivate", $this->credential, $this->secret, ['reason' => 'auto'], ['Idempotency-Key' => 'agente-tentou-1'])
            ->assertForbidden();

        $this->assertSame(0, RemoteCommand::count());
        $this->assertFalse(collect(ApiCredential::INSTALLATION_ABILITIES)->contains('commands:issue'));

        $this->expectException(\InvalidArgumentException::class);
        app(CredentialService::class)->issueForInstallation($this->project, 'x', abilities: ['commands:issue']);
    }

    public function test_revoked_and_expired_credentials_stop_working_immediately(): void
    {
        $uri = "/api/v1/projects/{$this->project->uuid}/status";
        app(CredentialService::class)->revoke($this->credential, 'servidor comprometido');

        $this->signedJson('GET', $uri, $this->credential, $this->secret)->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', ['action' => 'REVOKE', 'resource_label' => $this->credential->key_id]);
    }

    public function test_rotation_keeps_the_old_secret_valid_only_during_the_grace_window(): void
    {
        ['credential' => $new, 'secret' => $newSecret] = app(CredentialService::class)->rotate($this->credential);
        $uri = "/api/v1/projects/{$this->project->uuid}/status";

        $this->signedJson('GET', $uri, $new, $newSecret)->assertOk();
        $this->signedJson('GET', $uri, $this->credential, $this->secret)->assertOk();

        Carbon::setTestNow(now()->addHours(25));
        $this->signedJson('GET', $uri, $this->credential, $this->secret)->assertUnauthorized();
        $this->signedJson('GET', $uri, $new, $newSecret)->assertOk();
    }

    public function test_secret_is_stored_encrypted_and_hidden_from_serialization(): void
    {
        $raw = \DB::table('api_credentials')->where('id', $this->credential->id)->value('secret');

        $this->assertNotSame($this->secret, $raw);
        $this->assertArrayNotHasKey('secret', $this->credential->toArray());
    }

    public function test_https_is_required_when_enabled(): void
    {
        config(['gestao.api.require_https' => true]);

        $this->signedJson('GET', "/api/v1/projects/{$this->project->uuid}/status", $this->credential, $this->secret)
            ->assertForbidden()->assertJsonPath('error.code', 'tls_required');
    }

    public function test_rate_limit_per_credential(): void
    {
        config(['gestao.api.rate_limit_per_minute' => 3]);
        $uri = "/api/v1/projects/{$this->project->uuid}/status";

        foreach (range(1, 3) as $i) {
            $this->signedJson('GET', $uri, $this->credential, $this->secret)->assertOk();
        }
        $this->signedJson('GET', $uri, $this->credential, $this->secret)->assertStatus(429);
    }

    public function test_heartbeat_keeps_only_whitelisted_metrics(): void
    {
        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/health", $this->credential, $this->secret, [
            'state' => 'active',
            'deployed_version' => '1.4.2',
            'last_backup_at' => now()->subHours(3)->toIso8601String(),
            'app_errors_last_hour' => 2,
            'resources' => ['cpu_load' => 0.4, 'memory_used_pct' => 61, 'disk_used_pct' => 70, 'db_password' => 'segredo'],
            'dependencies' => [['name' => 'mysql', 'ok' => true, 'latency_ms' => 3], ['name' => 'redis', 'ok' => true]],
            'customer_emails' => ['fulano@example.com'],
            'env' => ['APP_KEY' => 'base64:xxx'],
        ])->assertStatus(202);

        $check = $this->project->healthChecks()->sole();
        $stored = json_encode($check->toArray());
        $this->assertTrue($check->is_up);
        $this->assertSame('1.4.2', $this->project->refresh()->deployed_version);
        $this->assertNotNull($this->project->agent_last_seen_at);
        $this->assertStringNotContainsString('segredo', $stored);
        $this->assertStringNotContainsString('fulano@example.com', $stored);
        $this->assertStringNotContainsString('APP_KEY', $stored);
    }

    public function test_automation_issues_idempotent_commands(): void
    {
        ['credential' => $auto, 'secret' => $autoSecret] = $this->automation();
        $uri = "/api/v1/projects/{$this->project->uuid}/commands/maintenance";
        $body = ['reason' => 'Atualização do servidor', 'message' => 'Voltamos às 23h', 'starts_at' => now()->addHour()->toIso8601String(), 'ends_at' => now()->addHours(2)->toIso8601String()];

        $this->signedJson('POST', $uri, $auto, $autoSecret, $body)->assertStatus(400)->assertJsonPath('error.code', 'idempotency_key_required');

        $first = $this->signedJson('POST', $uri, $auto, $autoSecret, $body, ['Idempotency-Key' => 'manut-2026-10-14'])->assertCreated();
        $replay = $this->signedJson('POST', $uri, $auto, $autoSecret, $body, ['Idempotency-Key' => 'manut-2026-10-14'])->assertOk();

        $this->assertSame($first->json('data.id'), $replay->json('data.id'));
        $this->assertSame(1, RemoteCommand::count());

        $this->signedJson('POST', $uri, $auto, $autoSecret, ['reason' => 'Outro motivo'], ['Idempotency-Key' => 'manut-2026-10-14'])
            ->assertStatus(409)->assertJsonPath('error.code', 'idempotency_conflict');
    }

    public function test_automation_suspend_is_refused_with_guard_report_when_contract_does_not_allow(): void
    {
        ['credential' => $auto, 'secret' => $autoSecret] = $this->automation();
        $contract = Contract::factory()->active()->create(['client_project_id' => $this->project->id, 'client_id' => $this->project->client_id]);
        $contract->policy()->create(BillingPolicy::defaults());
        $charge = Charge::factory()->create(['client_project_id' => $this->project->id, 'contract_id' => $contract->id, 'due_date' => now()->subDays(40)]);
        app(DunningService::class)->process();

        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/suspend", $auto, $autoSecret, ['reason' => 'atraso', 'charge_id' => $charge->uuid], ['Idempotency-Key' => 'susp-'.$charge->id.'-x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'not_allowed')
            ->assertJsonPath('error.guard.allowed', false);

        $this->assertSame(0, RemoteCommand::count());
    }

    public function test_agent_pulls_signed_commands_and_acknowledges(): void
    {
        ['credential' => $auto, 'secret' => $autoSecret] = $this->automation();
        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/maintenance", $auto, $autoSecret, ['reason' => 'Janela'], ['Idempotency-Key' => 'janela-0001'])->assertCreated();

        $response = $this->signedJson('GET', "/api/v1/projects/{$this->project->uuid}/commands/pending", $this->credential, $this->secret)->assertOk();
        $commandId = $response->json('data.0.id');

        $this->assertSame('maintenance', $response->json('data.0.type'));
        $this->assertSame('maintenance_page', $response->json('data.0.mode'));
        $this->assertSame(
            RequestSigner::signResponse($this->secret, $response->headers->get('X-Response-Timestamp'), $response->getContent()),
            $response->headers->get('X-Response-Signature'),
        );

        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/{$commandId}/ack", $this->credential, $this->secret, ['status' => 'executed', 'message' => 'ok'])
            ->assertOk()->assertJsonPath('data.status', 'executed');

        $this->assertSame(CommandStatus::Executed, RemoteCommand::sole()->status);
        $this->assertSame('scheduled_maintenance', $this->project->refresh()->operational_status->value);
        $this->signedJson('GET', "/api/v1/projects/{$this->project->uuid}/commands/pending", $this->credential, $this->secret)->assertOk()->assertJsonCount(0, 'data');

        // Heartbeat informando que a janela terminou devolve o projeto para "ativo".
        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/health", $this->credential, $this->secret, ['state' => 'active'])->assertStatus(202);
        $this->assertSame('active', $this->project->refresh()->operational_status->value);
    }

    public function test_panel_times_are_read_in_the_operation_timezone_and_api_offsets_are_kept(): void
    {
        $admin = User::factory()->admin()->create();
        config(['gestao.timezone' => 'America/Sao_Paulo']);

        $this->actingAs($admin)->post("/admin/gestao/projetos/{$this->project->uuid}/comandos", [
            'idempotency_key' => 'tz-1', 'type' => 'maintenance', 'reason' => 'Janela', 'starts_at' => '2026-10-20T22:00', 'ends_at' => '2026-10-20T23:30',
        ])->assertSessionHasNoErrors();

        $command = RemoteCommand::sole();
        $this->assertSame('2026-10-21T01:00:00+00:00', $command->starts_at->toIso8601String());
        $this->assertSame('2026-10-21T02:30:00+00:00', $command->ends_at->toIso8601String());

        ['credential' => $auto, 'secret' => $autoSecret] = $this->automation();
        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/maintenance", $auto, $autoSecret, ['reason' => 'x', 'ends_at' => '2026-10-20T23:30:00+00:00'], ['Idempotency-Key' => 'tz-api-1'])->assertCreated();
        $this->assertSame('2026-10-20T23:30:00+00:00', RemoteCommand::latest('id')->first()->ends_at->toIso8601String());
    }

    public function test_agent_cannot_acknowledge_commands_of_other_projects(): void
    {
        $other = ClientProject::factory()->create();
        $command = RemoteCommand::create(['client_project_id' => $other->id, 'type' => 'maintenance', 'status' => 'queued', 'channel' => 'agent', 'idempotency_key' => 'x-1', 'request_hash' => str_repeat('0', 64), 'reason' => 'x', 'triggered_by_type' => 'rule', 'triggered_by_label' => 'x']);

        $this->signedJson('POST', "/api/v1/projects/{$this->project->uuid}/commands/{$command->uuid}/ack", $this->credential, $this->secret, ['status' => 'executed'])->assertNotFound();
        $this->assertSame(CommandStatus::Queued, $command->refresh()->status);
    }
}
