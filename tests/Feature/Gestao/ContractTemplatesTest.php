<?php

namespace Tests\Feature\Gestao;

use App\Enums\DocumentType;
use App\Models\ClientProject;
use App\Models\ContractTemplate;
use App\Models\ProjectValidation;
use App\Models\ScopeRequest;
use App\Models\User;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\DocumentService;
use App\Support\DefaultContractClauses;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected ClientProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gestao.company' => ['name' => 'Empresa Teste Ltda', 'document' => '11222333000181', 'email' => 'contato@empresa.test', 'city' => 'São Paulo']]);
        $this->seed(ContractTemplateSeeder::class);

        $this->project = ClientProject::factory()->create(['objective' => 'Automatizar pedidos', 'plan_name' => 'Essencial', 'technologies' => 'Laravel']);
        $client = $this->project->client;
        $client->forceFill(['document' => '11444777000161', 'address_street' => 'Rua A', 'address_number' => '10', 'address_city' => 'Campinas', 'address_state' => 'SP'])->save();
        $client->contacts()->create(['name' => 'Ana Souza', 'role' => 'contract', 'email' => 'ana@cliente.test', 'document' => '52998224725', 'is_primary' => true]);

        app(ContractService::class)->create($this->project, [
            'object' => 'Desenvolvimento do sistema de pedidos',
            'scope' => 'Cadastro de clientes e pedidos.',
            'deliverables' => 'Código-fonte e implantação.',
            'setup_fee_cents' => 500000,
            'monthly_fee_cents' => 60000,
            'payment_method' => 'Pix',
            'payment_terms' => '50% na assinatura e 50% na entrega.',
            'billing_day' => 10,
            'starts_at' => now(),
            'renewal_rules' => 'Renovação automática por prazo indeterminado.',
            'clauses' => DefaultContractClauses::all(),
            'support_sla' => ['urgent' => ['response_hours' => 4, 'resolution_hours' => 24]],
        ], null);

        $this->project->domains()->create(['client_id' => $client->id, 'name' => 'pedidos.example.com', 'registrar' => 'Registro.br', 'registrant_name' => 'Cliente', 'managed_by' => 'us', 'payer' => 'client', 'expires_at' => now()->addYear()]);
        $this->project->hostingServices()->create(['client_id' => $client->id, 'provider' => 'VPS', 'plan' => '2GB', 'resources' => '2 vCPU', 'managed_by' => 'us', 'payer' => 'client', 'monthly_cost_cents' => 8000, 'renews_at' => now()->addMonth(), 'limits_and_responsibilities' => 'Até 40GB.', 'backup_policy' => 'Diário, 7 dias.', 'migration_procedure' => 'Exportação em até 10 dias.']);
    }

    public function test_every_template_is_complete_when_the_registration_is_complete(): void
    {
        $validation = ProjectValidation::create(['client_project_id' => $this->project->id, 'version' => '1.0', 'environment' => 'staging', 'environment_url' => 'https://h.example.com', 'features_evaluated' => 'Pedidos', 'tests_performed' => 'Fluxo completo', 'evidence' => 'Vídeo anexo']);
        $validation->forceFill(['outcome' => 'approved', 'responded_at' => now(), 'responded_by' => User::factory()->portal($this->project->client)->create()->id, 'client_notes' => 'Ok'])->save();
        $scope = ScopeRequest::create(['client_project_id' => $this->project->id, 'title' => 'Relatório', 'description' => 'PDF mensal', 'reason' => 'Gestão', 'affected_features' => 'Relatórios', 'impact_analysis' => 'Baixo', 'estimated_hours' => 8, 'amount_cents' => 80000, 'estimated_days' => 5, 'dependencies' => 'Nenhuma', 'acceptance_criteria' => 'Gera PDF']);
        $this->project->forceFill(['deployed_version' => '1.0.0'])->save();

        foreach (DocumentType::cases() as $type) {
            $document = app(DocumentService::class)->generate($this->project->refresh(), $type, null,
                scope: $type === DocumentType::ScopeChange ? $scope : null,
                validation: $type === DocumentType::AcceptanceTerm ? $validation : null);

            preg_match_all('/\[preencher: ([a-z0-9_.]+)\]/', $document->body_html, $missing);
            $this->assertSame([], $missing[1], "{$type->label()} ficou com campos vazios.");
            $this->assertStringNotContainsString('[descrever', $document->body_html);
            $this->assertStringContainsString('Assinaturas', $document->body_html);
            $this->assertStringContainsString('Empresa Teste Ltda', $document->body_html);
        }
    }

    public function test_new_contract_form_comes_prefilled_with_default_clauses_and_sla(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/gestao/projetos/{$this->project->uuid}/contratos/criar")
            ->assertOk()
            ->assertSee('Garantia de 90 (noventa) dias', false)
            ->assertSee('IPCA', false)
            ->assertSee('name="sla[urgent][response_hours]"', false);
    }

    public function test_seeder_updates_unreviewed_base_templates_but_never_reviewed_ones(): void
    {
        $base = ContractTemplate::where('document_type', 'closure')->sole();
        $reviewed = ContractTemplate::where('document_type', 'acceptance_term')->sole();
        $base->forceFill(['body' => '<p>antigo</p>'])->save();
        $reviewed->forceFill(['body' => '<p>revisado pelo jurídico</p>', 'legal_reviewed_at' => now()])->save();

        $this->seed(ContractTemplateSeeder::class);

        $this->assertStringContainsString('Termo de Encerramento', $base->refresh()->body);
        $this->assertSame(2, $base->version);
        $this->assertSame('<p>revisado pelo jurídico</p>', $reviewed->refresh()->body);
    }
}
