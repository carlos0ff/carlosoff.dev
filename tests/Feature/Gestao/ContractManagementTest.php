<?php

namespace Tests\Feature\Gestao;

use App\Enums\ContractStatus;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use Database\Seeders\GestaoPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_is_created_as_draft_with_safe_default_policy_and_versioned_edits(): void
    {
        $admin = User::factory()->admin()->create();
        $project = ClientProject::factory()->create();

        $this->actingAs($admin)->post("/admin/gestao/projetos/{$project->uuid}/contratos", [
            'object' => 'Desenvolvimento do portal',
            'monthly_fee' => '450,00',
            'setup_fee' => '3.000,00',
            'billing_day' => 10,
            'clauses' => ['warranty' => '90 dias para defeitos'],
            'sla' => ['urgent' => ['response_hours' => 4, 'resolution_hours' => 24], 'low' => ['response_hours' => '', 'resolution_hours' => '']],
        ])->assertSessionHasNoErrors();

        $contract = Contract::sole();
        $this->assertSame(ContractStatus::Draft, $contract->status);
        $this->assertSame(1, $contract->version);
        $this->assertSame(45000, $contract->monthly_fee_cents);
        $this->assertSame(['urgent' => ['response_hours' => 4, 'resolution_hours' => 24]], $contract->support_sla);
        $this->assertFalse($contract->policy->suspension_allowed, 'Suspensão desabilitada por padrão.');
        $this->assertSame('draft', $project->refresh()->contract_status->value);

        $this->actingAs($admin)->put("/admin/gestao/contratos/{$contract->id}", ['object' => 'Desenvolvimento do portal v2', 'change_summary' => 'Ajuste do objeto'])->assertSessionHasNoErrors();
        $this->assertSame(2, $contract->refresh()->version);
        $this->assertSame(2, $contract->versions()->count());

        // Política exige cláusula de referência para permitir suspensão.
        $policy = ['reminder_days_before' => 3, 'overdue_notice_interval_days' => 3, 'grace_days' => 15, 'pre_suspension_notice_days' => 7, 'suspension_allowed' => 1, 'restriction_mode' => 'maintenance_page', 'reactivation_mode' => 'automatic'];
        $this->actingAs($admin)->put("/admin/gestao/contratos/{$contract->id}/politica", $policy)->assertSessionHasErrors('contract_clause_reference');
        $this->actingAs($admin)->put("/admin/gestao/contratos/{$contract->id}/politica", $policy + ['contract_clause_reference' => 'Cláusula 9.2'])->assertSessionHasNoErrors();
        $this->assertSame(15, $contract->policy()->first()->grace_days);

        // Ativo: não pode mais ser reescrito.
        $this->actingAs($admin)->post("/admin/gestao/contratos/{$contract->id}/ativar", ['justification' => 'Assinado em papel'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/gestao/contratos/{$contract->id}", ['object' => 'Outro', 'change_summary' => 'x'])->assertSessionHasErrors('contract');
        $this->assertSame('active', $project->refresh()->contract_status->value);
    }

    public function test_pilot_seeder_builds_a_consistent_end_to_end_scenario_and_is_idempotent(): void
    {
        Notification::fake();

        $this->seed(GestaoPilotSeeder::class);
        $this->seed(GestaoPilotSeeder::class);

        $project = ClientProject::sole();
        $this->assertSame('active', $project->contract_status->value);
        $this->assertSame('overdue', $project->financial_status->value);
        $this->assertSame(1, $project->contracts()->count());
        $this->assertSame(7, ContractTemplate::count());
    }
}
