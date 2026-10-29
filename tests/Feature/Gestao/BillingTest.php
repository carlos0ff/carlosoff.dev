<?php

namespace Tests\Feature\Gestao;

use App\Enums\ChargeStatus;
use App\Enums\FinancialStatus;
use App\Enums\PaymentSource;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\User;
use App\Services\Billing\ChargeService;
use App\Services\Billing\RecurringChargeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_issuing_a_charge_is_not_counted_as_received_money(): void
    {
        $admin = User::factory()->admin()->create();
        $contract = Contract::factory()->active()->create(['monthly_fee_cents' => 0]);

        $this->actingAs($admin)->post('/admin/gestao/cobrancas', [
            'client_project_id' => $contract->client_project_id,
            'kind' => 'additional',
            'description' => 'Relatório extra',
            'amount' => '1.250,50',
            'due_date' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $charge = Charge::sole();
        $this->assertSame(125050, $charge->amount_cents);
        $this->assertSame(0, $charge->paid_cents);
        $this->assertSame(ChargeStatus::Pending, $charge->status);
        $this->assertSame(FinancialStatus::Pending, $contract->project->refresh()->financial_status);

        $response = $this->actingAs($admin)->get('/admin/gestao?period='.now()->format('Y-m'));
        $response->assertOk();
        $this->assertSame(125050, $response->viewData('finance')['invoiced']);
        $this->assertSame(0, $response->viewData('finance')['received']);
        $this->assertSame(125050, $response->viewData('finance')['forecast']);
    }

    public function test_manual_payment_requires_evidence_and_records_the_responsible_user(): void
    {
        $finance = User::factory()->staff('financeiro')->create();
        $charge = Charge::factory()->create(['amount_cents' => 50000]);

        $this->actingAs($finance)->post("/admin/gestao/cobrancas/{$charge->uuid}/pagamentos", [
            'amount' => '500,00', 'paid_at' => now()->subHour()->format('Y-m-d H:i'), 'method' => 'pix',
        ])->assertSessionHasErrors('evidence_notes');
        $this->assertSame(0, $charge->payments()->count());

        $this->actingAs($finance)->post("/admin/gestao/cobrancas/{$charge->uuid}/pagamentos", [
            'amount' => '500,00', 'paid_at' => now()->subHour()->format('Y-m-d H:i'), 'method' => 'pix',
            'evidence' => UploadedFile::fake()->create('comprovante.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $charge->refresh();
        $payment = $charge->payments()->sole();
        $this->assertSame(ChargeStatus::Paid, $charge->status);
        $this->assertSame($finance->id, $payment->recorded_by);
        $this->assertNotNull($payment->evidence_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT', 'client_project_id' => $charge->client_project_id, 'user_id' => $finance->id]);
    }

    public function test_partial_payment_overdue_and_refund_keep_consistent_totals(): void
    {
        $service = app(ChargeService::class);
        $charge = Charge::factory()->create(['amount_cents' => 100000, 'due_date' => now()->subDays(3)]);

        $service->recordPayment($charge, 40000, now(), PaymentSource::Manual, 'pix', evidenceNotes: 'E2E 123');
        $this->assertSame(ChargeStatus::Overdue, $charge->refresh()->status);
        $this->assertSame(60000, $charge->outstandingCents());

        $service->recordPayment($charge, 60000, now(), PaymentSource::Manual, 'pix', evidenceNotes: 'E2E 456');
        $this->assertSame(ChargeStatus::Paid, $charge->refresh()->status);
        $this->assertSame(FinancialStatus::Regularized, $charge->project->refresh()->financial_status);

        $service->refund($charge, 100000, 'Cobrança indevida');
        $this->assertSame(ChargeStatus::Refunded, $charge->refresh()->status);
        $this->assertSame(0, $charge->paid_cents);

        $this->expectException(InvalidArgumentException::class);
        $service->refund($charge, 1, 'Nada a estornar');
    }

    public function test_webhook_payments_are_idempotent_by_provider_id(): void
    {
        $service = app(ChargeService::class);
        $charge = Charge::factory()->create(['amount_cents' => 50000]);

        $service->recordPayment($charge, 50000, now(), PaymentSource::Webhook, 'pix', 'pay_abc');
        $service->recordPayment($charge->refresh(), 50000, now(), PaymentSource::Webhook, 'pix', 'pay_abc');

        $this->assertSame(1, $charge->payments()->count());
        $this->assertSame(50000, $charge->refresh()->paid_cents);
    }

    public function test_cancelling_requires_no_received_amount(): void
    {
        $service = app(ChargeService::class);
        $charge = Charge::factory()->create();
        $service->recordPayment($charge, 1000, now(), PaymentSource::Manual, evidenceNotes: 'parcial');

        $this->expectException(InvalidArgumentException::class);
        $service->cancel($charge->refresh(), 'erro');
    }

    public function test_recurring_generation_is_idempotent_and_respects_contract_dates(): void
    {
        Carbon::setTestNow('2026-10-05');
        $contract = Contract::factory()->active()->create(['billing_day' => 10, 'monthly_fee_cents' => 45000, 'starts_at' => '2026-09-01']);

        $generator = app(RecurringChargeGenerator::class);
        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertCount(1, $first, 'Somente outubro vence dentro da janela de 20 dias.');
        $this->assertCount(0, $second);
        $this->assertDatabaseHas('charges', ['contract_id' => $contract->id, 'competence' => '2026-10', 'amount_cents' => 45000, 'due_date' => '2026-10-10 00:00:00']);

        Carbon::setTestNow('2026-10-25');
        $this->assertCount(1, $generator->generate(), 'Novembro entra na janela.');
        $this->assertSame(2, Charge::count());

        // Contrato encerrado não gera mais nada.
        $contract->forceFill(['status' => 'ended'])->save();
        Carbon::setTestNow('2026-11-25');
        $this->assertCount(0, $generator->generate());
    }

    public function test_cancelled_and_draft_contracts_do_not_generate_charges(): void
    {
        Contract::factory()->create(['monthly_fee_cents' => 45000]);

        $this->assertCount(0, app(RecurringChargeGenerator::class)->generate());
    }
}
