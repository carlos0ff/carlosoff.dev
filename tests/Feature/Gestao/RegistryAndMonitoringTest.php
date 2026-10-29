<?php

namespace Tests\Feature\Gestao;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\User;
use App\Services\Monitoring\HealthProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistryAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_registration_validates_cnpj_and_uniqueness(): void
    {
        $admin = User::factory()->admin()->create();
        $data = ['person_type' => 'pj', 'legal_name' => 'Acme Ltda', 'email' => 'contato@acme.test', 'status' => 'active'];

        $this->actingAs($admin)->post('/admin/gestao/clientes', $data + ['document' => '11.111.111/1111-11'])->assertSessionHasErrors('document');
        $this->actingAs($admin)->post('/admin/gestao/clientes', $data + ['document' => '11.222.333/0001-81'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/gestao/clientes', $data + ['document' => '11222333000181'])->assertSessionHasErrors('document');

        $this->assertSame('11222333000181', Client::sole()->document);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE', 'resource_type' => 'Cliente']);
    }

    public function test_project_keeps_four_independent_states_and_client_cannot_be_changed(): void
    {
        $admin = User::factory()->admin()->create();
        $project = ClientProject::factory()->create();
        $other = Client::factory()->create();

        $this->actingAs($admin)->put("/admin/gestao/projetos/{$project->uuid}", ['client_id' => $other->id, 'name' => 'X', 'development_status' => 'staging'])->assertSessionHasErrors('client_id');

        // Campos financeiro/operacional não são aceitos pelo formulário.
        $this->actingAs($admin)->put("/admin/gestao/projetos/{$project->uuid}", ['client_id' => $project->client_id, 'name' => 'X', 'development_status' => 'staging', 'financial_status' => 'up_to_date', 'operational_status' => 'suspended'])->assertSessionHasNoErrors();
        $project->refresh();
        $this->assertSame('staging', $project->development_status->value);
        $this->assertSame('active', $project->operational_status->value);
    }

    public function test_probe_records_availability_and_opens_technical_incident_after_consecutive_failures(): void
    {
        Notification::fake();
        $project = ClientProject::factory()->create(['production_url' => 'http://app.example.test/?token=segredo']);

        Http::fake(['*' => Http::sequence()->push('erro', 503)->push('erro', 503)->push('erro', 503)->push('ok', 200)]);
        foreach (range(1, 3) as $i) {
            app(HealthProbe::class)->probe($project);
        }
        $this->assertSame(1, $project->incidents()->whereNull('resolved_at')->count());
        $this->assertStringNotContainsString('segredo', json_encode($project->healthChecks()->get()));

        $check = app(HealthProbe::class)->probe($project);
        $this->assertTrue($check->is_up);
        $this->assertSame(0, $project->incidents()->whereNull('resolved_at')->count());
        $this->assertSame('up_to_date', $project->refresh()->financial_status->value);
    }

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::record(AuditAction::Create, 'Teste', 1, 'item');

        $log->details = 'alterado';
        $log->save();
        $log->delete();

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'details' => null]);
    }
}
