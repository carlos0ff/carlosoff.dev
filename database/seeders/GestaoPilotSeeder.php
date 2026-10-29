<?php

namespace Database\Seeders;

use App\Enums\ChargeKind;
use App\Enums\DevelopmentStatus;
use App\Enums\PaymentSource;
use App\Models\Client;
use App\Models\ClientProject;
use App\Services\Billing\ChargeService;
use App\Services\Contracts\ContractService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Projeto piloto para validar o fluxo de ponta a ponta (dados fictícios, claramente identificados).
 * `php artisan db:seed --class=GestaoPilotSeeder` — idempotente pelo e-mail do cliente piloto.
 */
class GestaoPilotSeeder extends Seeder
{
    public function run(ContractService $contracts, ChargeService $charges): void
    {
        $this->call(ContractTemplateSeeder::class);

        if (Client::where('email', 'piloto@cliente-piloto.test')->exists()) {
            $this->command?->info('Piloto já existe.');

            return;
        }

        DB::transaction(fn () => $this->createPilot($contracts, $charges));
    }

    protected function createPilot(ContractService $contracts, ChargeService $charges): void
    {
        $client = Client::create([
            'person_type' => 'pj',
            'legal_name' => 'Cliente Piloto Ltda (TESTE)',
            'trade_name' => 'Cliente Piloto',
            'email' => 'piloto@cliente-piloto.test',
            'billing_email' => 'financeiro@cliente-piloto.test',
            'phone' => '(11) 3000-0000',
            'address_street' => 'Rua de Teste',
            'address_number' => '100',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'address_zip' => '01000-000',
            'status' => 'active',
            'relationship_started_at' => now()->subMonths(3),
            'internal_notes' => 'Cliente fictício para homologação do módulo de gestão.',
        ]);
        $client->contacts()->create(['name' => 'Responsável Piloto', 'role' => 'contract', 'email' => 'responsavel@cliente-piloto.test', 'is_primary' => true]);
        $client->contacts()->create(['name' => 'Financeiro Piloto', 'role' => 'financial', 'email' => 'financeiro@cliente-piloto.test']);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'name' => 'Projeto Piloto',
            'description' => 'Sistema de demonstração usado para validar contratos, cobranças, API e agente.',
            'objective' => 'Validar o ciclo completo de gestão.',
            'technologies' => 'Laravel, MySQL',
            'primary_domain' => 'piloto.example.com',
            'staging_url' => 'https://homolog.piloto.example.com',
            'production_url' => 'https://example.com',
            'started_at' => now()->subMonths(3),
            'due_at' => now()->addMonth(),
            'published_at' => now()->subMonth(),
            'development_status' => DevelopmentStatus::Staging,
            'plan_name' => 'Essencial',
            'setup_fee_cents' => 300000,
            'monthly_fee_cents' => 45000,
            'billing_day' => 10,
            'monitoring_enabled' => true,
        ]);

        $project->domains()->create(['client_id' => $client->id, 'name' => 'piloto.example.com', 'registrar' => 'Registro.br', 'registrant_name' => 'Cliente Piloto Ltda (TESTE)', 'managed_by' => 'us', 'payer' => 'client', 'expires_at' => now()->addDays(20)]);
        $project->hostingServices()->create(['client_id' => $client->id, 'provider' => 'Provedor de Teste', 'plan' => 'VPS 2GB', 'managed_by' => 'us', 'payer' => 'client', 'monthly_cost_cents' => 8000, 'renews_at' => now()->addDays(15), 'backup_policy' => 'Backup diário com retenção de 7 dias.']);

        $contract = $contracts->create($project, [
            'object' => 'Desenvolvimento e manutenção do Projeto Piloto',
            'scope' => 'Cadastro, relatórios e painel administrativo.',
            'deliverables' => 'Código-fonte, implantação em produção e documentação de uso.',
            'setup_fee_cents' => 300000,
            'monthly_fee_cents' => 45000,
            'payment_method' => 'Pix',
            'billing_day' => 10,
            'starts_at' => now()->subMonths(2)->startOfMonth(),
            'clauses' => ['suspension_rules' => 'Após a tolerância e o aviso prévio, poderá ser exibida página de manutenção transparente até a regularização.'],
            'support_sla' => ['urgent' => ['response_hours' => 4, 'resolution_hours' => 24], 'normal' => ['response_hours' => 24, 'resolution_hours' => 120]],
        ], null);
        $contracts->updatePolicy($contract, ['suspension_allowed' => true, 'restriction_mode' => 'maintenance_page', 'contract_clause_reference' => 'Cláusula 9', 'regularization_instructions' => 'Pagamento via Pix e envio do comprovante pelo portal.']);
        $contracts->activate($contract, 'Piloto: contrato ativado para homologação');

        $paid = $charges->create($project, ['contract_id' => $contract->id, 'kind' => ChargeKind::Setup, 'description' => 'Implantação', 'amount_cents' => 300000, 'due_date' => now()->subMonths(2)]);
        $charges->recordPayment($paid, 300000, Carbon::now()->subMonths(2), PaymentSource::Manual, 'pix', evidenceNotes: 'Piloto: Pix E2E fictício');

        $charges->create($project, ['contract_id' => $contract->id, 'kind' => ChargeKind::Monthly, 'description' => 'Mensalidade (piloto vencida)', 'competence' => now()->subMonth()->format('Y-m'), 'amount_cents' => 45000, 'due_date' => now()->subDays(20)]);

        $this->command?->info("Piloto criado: projeto {$project->uuid}");
    }
}
