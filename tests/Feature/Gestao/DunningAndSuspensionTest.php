<?php

namespace Tests\Feature\Gestao;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\OperationalStatus;
use App\Enums\PaymentSource;
use App\Models\Charge;
use App\Models\ChargeNotice;
use App\Models\Contract;
use App\Models\HealthCheck;
use App\Models\RemoteCommand;
use App\Models\User;
use App\Notifications\ClientMessage;
use App\Services\Billing\ChargeService;
use App\Services\Billing\DunningService;
use App\Services\Billing\Gateways\GatewayUnavailableException;
use App\Services\Billing\Gateways\ManualGateway;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Monitoring\IncidentTracker;
use App\Services\Operations\Actor;
use App\Services\Operations\CommandNotAllowedException;
use App\Services\Operations\ReactivationService;
use App\Services\Operations\RemoteCommandService;
use App\Services\Operations\SuspensionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DunningAndSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-01 08:00');
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array{0: Contract, 1: Charge}
     */
    protected function scenario(array $policy = [], string $dueDate = '2026-10-10'): array
    {
        $contract = Contract::factory()->active()->withSuspensionPolicy($policy)->create();
        $contract->project->client->portalUsers()->save(User::factory()->make(['role' => 'cliente']));
        $charge = app(ChargeService::class)->create($contract->project, ['contract_id' => $contract->id, 'kind' => 'monthly', 'description' => 'Mensalidade 10/2026', 'amount_cents' => 45000, 'due_date' => $dueDate]);

        return [$contract, $charge];
    }

    protected function runOn(string $date): void
    {
        Carbon::setTestNow($date.' 09:00');
        app(DunningService::class)->process();
    }

    /**
     * @return list<string>
     */
    protected function stages(Charge $charge): array
    {
        return ChargeNotice::where('charge_id', $charge->id)->orderBy('id')->get()->map(fn ($n) => "{$n->stage}#{$n->sequence}")->all();
    }

    public function test_full_configurable_flow_until_a_suspension_awaiting_human_approval(): void
    {
        [$contract, $charge] = $this->scenario();

        $this->runOn('2026-10-07'); // 3 dias antes
        $this->runOn('2026-10-10'); // vencimento: continua pendente
        $this->assertSame('pending', $charge->refresh()->status->value);
        $this->runOn('2026-10-11'); // 1 dia de atraso
        $this->assertSame('overdue', $charge->refresh()->status->value);
        $this->runOn('2026-10-12'); // mesmo período de aviso: não repete
        $this->runOn('2026-10-14'); // 4 dias: 2º aviso (intervalo 3)
        $this->runOn('2026-10-20'); // 10 dias: ainda na tolerância (período de aviso 4; o 3 não teve execução)

        $this->assertSame(['reminder#1', 'due#1', 'overdue#1', 'overdue#2', 'overdue#4'], $this->stages($charge));
        $this->assertSame(0, RemoteCommand::count(), 'Durante a tolerância o serviço é mantido.');

        $this->runOn('2026-10-21'); // 11 dias: fim da tolerância → alerta interno + aviso prévio
        $this->assertContains('grace_expired#1', $this->stages($charge));
        $this->assertContains('pre_suspension#1', $this->stages($charge));
        $this->assertSame('2026-10-26', $charge->refresh()->suspension_eligible_at->toDateString());
        $this->assertSame(0, RemoteCommand::count(), 'Aviso prévio antes de qualquer proposta.');

        $this->runOn('2026-10-25');
        $this->assertSame(0, RemoteCommand::count());

        $this->runOn('2026-10-26');
        $command = RemoteCommand::sole();
        $this->assertSame(CommandType::Suspend, $command->type);
        $this->assertSame(CommandStatus::PendingApproval, $command->status, 'Nunca executa sozinha quando a política exige aprovação.');
        $this->assertSame('maintenance_page', $command->restriction_mode->value);
        $this->assertTrue($command->guard_report['allowed']);
        $this->assertStringContainsString('Regra: régua de cobrança', $command->triggered_by_label);
        $this->assertSame(OperationalStatus::Active, $contract->project->refresh()->operational_status);

        // Rodar de novo não duplica a proposta.
        $this->runOn('2026-10-27');
        $this->assertSame(1, RemoteCommand::count());

        Notification::assertSentTo($contract->project->client->portalUsers, ClientMessage::class, fn (ClientMessage $n) => str_contains($n->subject, 'Aviso prévio'));
    }

    public function test_dispute_negotiation_and_authorized_exception_block_suspension(): void
    {
        foreach (['dispute', 'negotiation', 'hold'] as $blocker) {
            Carbon::setTestNow('2026-10-01 08:00');
            [$contract, $charge] = $this->scenario();
            $this->runOn('2026-10-21');

            match ($blocker) {
                'dispute' => app(ChargeService::class)->dispute($charge->refresh(), 'Serviço não entregue no prazo'),
                'negotiation' => app(ChargeService::class)->negotiate($charge->refresh(), Carbon::parse('2026-11-30'), 'Parcelamento em 2x'),
                'hold' => $contract->project->forceFill(['suspension_hold_until' => '2026-12-31', 'suspension_hold_reason' => 'Acordo comercial'])->save(),
            };

            $this->runOn('2026-10-28');
            $this->assertSame(0, RemoteCommand::where('charge_id', $charge->id)->count(), "Bloqueio esperado: {$blocker}");

            $report = app(SuspensionGuard::class)->evaluate($charge->refresh());
            $this->assertFalse($report['allowed']);
        }
    }

    public function test_contract_without_suspension_clause_never_proposes_suspension(): void
    {
        [, $charge] = $this->scenario(['suspension_allowed' => false, 'restriction_mode' => 'none']);

        foreach (['2026-10-21', '2026-10-30', '2026-11-30'] as $day) {
            $this->runOn($day);
        }

        $this->assertSame(0, RemoteCommand::count());
        $this->assertNotContains('pre_suspension#1', $this->stages($charge));
        $this->assertContains('grace_expired#1', $this->stages($charge), 'A equipe ainda é alertada para avaliar.');
    }

    public function test_financial_source_failure_blocks_suspension(): void
    {
        [, $charge] = $this->scenario();
        $this->runOn('2026-10-21');

        app(PaymentGatewayManager::class)->extend('manual', FailingGateway::class);
        $this->runOn('2026-10-27');

        $this->assertSame(0, RemoteCommand::count());
        $report = app(SuspensionGuard::class)->evaluate($charge->refresh());
        $this->assertFalse(collect($report['checks'])->firstWhere('key', 'source')['passed']);
    }

    public function test_technical_incidents_never_trigger_suspension(): void
    {
        [$contract] = $this->scenario(dueDate: '2026-12-10');

        foreach (range(1, 5) as $i) {
            HealthCheck::create(['client_project_id' => $contract->client_project_id, 'source' => 'probe', 'is_up' => false, 'error' => 'HTTP 500', 'checked_at' => now()->addMinutes($i)]);
            app(IncidentTracker::class)->evaluate($contract->project, 'probe');
        }
        $this->runOn('2026-10-15');

        $this->assertSame(1, $contract->project->incidents()->count());
        $this->assertSame(0, RemoteCommand::count());
        $this->assertSame('pending', $contract->project->refresh()->financial_status->value);
    }

    public function test_approved_suspension_is_executed_by_agent_and_payment_reactivates_automatically(): void
    {
        [$contract, $charge] = $this->scenario();
        foreach (['2026-10-21', '2026-10-26'] as $day) {
            $this->runOn($day);
        }
        $commands = app(RemoteCommandService::class);
        $suspend = RemoteCommand::sole();

        $commands->approve($suspend, User::factory()->admin()->create());
        $this->assertSame(CommandStatus::Queued, $suspend->refresh()->status);

        $pulled = $commands->pullForAgent($contract->project);
        $this->assertCount(1, $pulled);
        $this->assertSame(CommandStatus::Delivered, $suspend->refresh()->status);

        $commands->acknowledge($suspend, CommandStatus::Executed, ['message' => 'página de manutenção ativa'], Actor::rule('agente'));
        $project = $contract->project->refresh();
        $this->assertSame(OperationalStatus::Suspended, $project->operational_status);
        $this->assertSame('maintenance_page', $project->active_restriction->value);

        // Ack repetido é ignorado.
        $commands->acknowledge($suspend->refresh(), CommandStatus::Failed, [], Actor::rule('agente'));
        $this->assertSame(CommandStatus::Executed, $suspend->refresh()->status);

        // Pagamento confirmado → reativação automática enfileirada (uma única vez).
        app(ChargeService::class)->recordPayment($charge->refresh(), 45000, now(), PaymentSource::Manual, 'pix', evidenceNotes: 'E2E 999');
        app(ReactivationService::class)->onChargeSettled($charge->refresh());

        $reactivate = RemoteCommand::where('type', 'reactivate')->sole();
        $this->assertSame(CommandStatus::Queued, $reactivate->status);
        $this->assertSame($suspend->id, $reactivate->reactivates_id);

        $commands->pullForAgent($project);
        $commands->acknowledge($reactivate, CommandStatus::Executed, [], Actor::rule('agente'));

        $project->refresh();
        $this->assertSame(OperationalStatus::Active, $project->operational_status);
        $this->assertNull($project->active_restriction);
        $this->assertSame('regularized', $project->financial_status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SUSPEND', 'client_project_id' => $project->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'REACTIVATE', 'client_project_id' => $project->id]);
    }

    public function test_manual_reactivation_policy_waits_for_approval(): void
    {
        [$contract, $charge] = $this->scenario(['reactivation_mode' => 'manual_approval']);
        foreach (['2026-10-21', '2026-10-26'] as $day) {
            $this->runOn($day);
        }
        $commands = app(RemoteCommandService::class);
        $suspend = RemoteCommand::sole();
        $commands->approve($suspend, User::factory()->admin()->create());
        $commands->pullForAgent($contract->project);
        $commands->acknowledge($suspend, CommandStatus::Executed, [], Actor::rule('agente'));

        app(ChargeService::class)->recordPayment($charge->refresh(), 45000, now(), PaymentSource::Manual, evidenceNotes: 'ok');

        $this->assertSame(CommandStatus::PendingApproval, RemoteCommand::where('type', 'reactivate')->sole()->status);
    }

    public function test_payment_before_approval_cancels_the_pending_suspension(): void
    {
        [, $charge] = $this->scenario();
        foreach (['2026-10-21', '2026-10-26'] as $day) {
            $this->runOn($day);
        }

        app(ChargeService::class)->recordPayment($charge->refresh(), 45000, now(), PaymentSource::Manual, evidenceNotes: 'ok');

        $this->assertSame(CommandStatus::Cancelled, RemoteCommand::sole()->status);
        $this->assertSame(0, RemoteCommand::where('type', 'reactivate')->count());
    }

    public function test_approval_rechecks_the_guard(): void
    {
        [, $charge] = $this->scenario();
        foreach (['2026-10-21', '2026-10-26'] as $day) {
            $this->runOn($day);
        }
        $suspend = RemoteCommand::sole();

        // Contestação registrada diretamente (simula mudança entre a proposta e a aprovação).
        $charge->refresh()->forceFill(['disputed_at' => now(), 'dispute_reason' => 'x'])->save();

        $this->expectException(CommandNotAllowedException::class);
        app(RemoteCommandService::class)->approve($suspend, User::factory()->admin()->create());
    }

    public function test_central_restriction_mode_applies_immediately_and_blocks_new_portal_requests(): void
    {
        [$contract, $charge] = $this->scenario(['restriction_mode' => 'block_new_requests', 'suspension_requires_approval' => false]);
        foreach (['2026-10-21', '2026-10-26'] as $day) {
            $this->runOn($day);
        }

        $command = RemoteCommand::sole();
        $this->assertSame('central', $command->channel);
        $this->assertSame(CommandStatus::Executed, $command->status);
        $this->assertTrue($contract->project->refresh()->blocksNewRequests());

        $portal = $contract->project->client->portalUsers()->first();
        $this->actingAs($portal)->post("/portal/projetos/{$contract->project->uuid}/solicitacoes", ['title' => 'Nova tela', 'description' => 'Detalhes'])
            ->assertSessionHasErrors('scope');
    }

    public function test_suspension_cannot_be_issued_without_an_overdue_charge(): void
    {
        $contract = Contract::factory()->active()->withSuspensionPolicy()->create();

        $this->expectException(CommandNotAllowedException::class);
        app(RemoteCommandService::class)->issue($contract->project, CommandType::Suspend, ['reason' => 'teste'], 'k-123456789', Actor::user(User::factory()->admin()->create()));
    }
}

class FailingGateway extends ManualGateway
{
    public function fetchStatus(Charge $charge): array
    {
        throw new GatewayUnavailableException('timeout');
    }
}
