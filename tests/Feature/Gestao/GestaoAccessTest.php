<?php

namespace Tests\Feature\Gestao;

use App\Enums\DocumentType;
use App\Models\Charge;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contracts\DocumentService;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestaoAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    protected function adminPages(): array
    {
        $this->seed(ContractTemplateSeeder::class);
        $contract = Contract::factory()->active()->withSuspensionPolicy()->create();
        $project = $contract->project;
        $charge = Charge::factory()->create(['client_project_id' => $project->id, 'contract_id' => $contract->id]);
        $document = app(DocumentService::class)->generate($project, DocumentType::DevelopmentContract, null);
        $scope = $project->scopeRequests()->create(['title' => 'Novo relatório', 'description' => 'Detalhes']);
        $ticket = $project->tickets()->create(['client_id' => $project->client_id, 'subject' => 'Erro', 'description' => 'x', 'priority' => 'normal']);

        return [
            '/admin/gestao', '/admin/gestao?period=2026-01&status=overdue', '/admin/gestao/clientes', '/admin/gestao/clientes/criar',
            "/admin/gestao/clientes/{$project->client_id}", "/admin/gestao/clientes/{$project->client_id}?aba=projetos", "/admin/gestao/clientes/{$project->client_id}?aba=financeiro", "/admin/gestao/clientes/{$project->client_id}?aba=contatos", "/admin/gestao/clientes/{$project->client_id}?aba=historico", "/admin/gestao/clientes/{$project->client_id}/editar",
            '/admin/gestao/projetos', '/admin/gestao/projetos/criar', "/admin/gestao/projetos/{$project->uuid}",
            "/admin/gestao/projetos/{$project->uuid}?aba=documentos", "/admin/gestao/projetos/{$project->uuid}?aba=financeiro", "/admin/gestao/projetos/{$project->uuid}?aba=operacao", "/admin/gestao/projetos/{$project->uuid}?aba=historico", "/admin/gestao/projetos/{$project->uuid}/editar",
            '/admin/gestao/contratos', "/admin/gestao/contratos/{$contract->id}", "/admin/gestao/contratos/{$contract->id}/editar", "/admin/gestao/projetos/{$project->uuid}/contratos/criar",
            '/admin/gestao/modelos', '/admin/gestao/modelos/1/editar',
            '/admin/gestao/documentos', "/admin/gestao/documentos/{$document->uuid}",
            '/admin/gestao/cobrancas', '/admin/gestao/cobrancas/criar', "/admin/gestao/cobrancas/{$charge->uuid}",
            '/admin/gestao/solicitacoes', "/admin/gestao/solicitacoes/{$scope->id}",
            '/admin/gestao/chamados', "/admin/gestao/chamados/{$ticket->id}",
            '/admin/gestao/monitoramento', '/admin/gestao/auditoria', '/admin/gestao/integracoes',
        ];
    }

    public function test_admin_can_open_every_management_page(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->adminPages() as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_guests_and_portal_users_cannot_open_management_pages(): void
    {
        $client = Client::factory()->create();
        $portal = User::factory()->portal($client)->create();

        $this->get('/admin/gestao')->assertRedirect(route('admin.login'));
        $this->get('/admin/gestao/clientes')->assertRedirect(route('admin.login'));
        $this->actingAs($portal)->get('/admin/gestao')->assertForbidden();
        $this->actingAs($portal)->get('/admin/gestao/cobrancas')->assertForbidden();
    }

    public function test_staff_cannot_use_the_client_portal(): void
    {
        $this->get('/portal')->assertRedirect(route('portal.login'));
        $this->actingAs(User::factory()->admin()->create())->get('/portal')->assertForbidden();
    }

    public function test_roles_limit_sensitive_actions(): void
    {
        $technical = User::factory()->staff('tecnico')->create();
        $finance = User::factory()->staff('financeiro')->create();
        $contract = Contract::factory()->active()->withSuspensionPolicy()->create();
        $charge = Charge::factory()->create(['client_project_id' => $contract->client_project_id, 'contract_id' => $contract->id]);

        // Técnico não dá baixa em pagamento nem altera política financeira.
        $this->actingAs($technical)->post("/admin/gestao/cobrancas/{$charge->uuid}/pagamentos", ['amount' => '500,00', 'paid_at' => now()->toDateTimeString(), 'method' => 'pix', 'evidence_notes' => 'x'])->assertForbidden();
        $this->actingAs($technical)->put("/admin/gestao/contratos/{$contract->id}/politica", [])->assertForbidden();

        // Financeiro não emite credenciais nem congela/envia documentos nem aprova comandos.
        $this->actingAs($finance)->post("/admin/gestao/projetos/{$contract->project->uuid}/credenciais", ['name' => 'x'])->assertForbidden();
        $this->actingAs($finance)->post("/admin/gestao/contratos/{$contract->id}/ativar", ['justification' => 'x'])->assertForbidden();

        $this->assertSame(0, $charge->payments()->count());
        $this->assertSame(0, $contract->project->credentials()->count());
    }

    public function test_panel_and_portal_routes_use_the_web_stack_with_csrf_and_the_api_does_not(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertContains('web', $routes->getByName('admin.gestao.clients.store')->gatherMiddleware());
        $this->assertContains('web', $routes->getByName('portal.scope.decide')->gatherMiddleware());
        $this->assertNotContains('web', $routes->getByName('api.v1.projects.status')->gatherMiddleware());
        $this->assertContains('api.signature', $routes->getByName('api.v1.projects.status')->gatherMiddleware());
    }
}
