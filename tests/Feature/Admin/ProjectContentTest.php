<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\PageView;
use App\Models\Project;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create(['name' => 'Carlos']));
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Projeto Completo',
            'acronym' => 'pc',
            'description' => 'Descrição curta do projeto.',
            'technologies' => 'Laravel, PHP',
            'overview' => "Primeiro parágrafo.\n\nSegundo parágrafo.",
            'objective' => 'Provar o fluxo ponta a ponta.',
            'features' => "Feature A\nFeature B",
            'architecture' => 'Monólito em camadas.',
            'development' => 'Desenvolvido em etapas.',
            'challenges' => [['title' => 'Desafio 1', 'problem' => 'Problema real.', 'solution' => 'Solução real.'], ['title' => '', 'problem' => 'ignorado', 'solution' => '']],
            'conclusion' => 'Conclusão do projeto.',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_the_form_fields_become_the_details_that_feed_the_public_page(): void
    {
        $this->post('/admin/projetos', $this->payload())->assertRedirect(route('admin.projects.index'));

        $project = Project::firstOrFail();
        $this->assertSame('projeto-completo', $project->slug);
        $this->assertSame('PC', strtoupper($project->acronym));
        $this->assertSame(['Primeiro parágrafo.', 'Segundo parágrafo.'], $project->details['overview']);
        $this->assertSame(['Feature A', 'Feature B'], $project->details['features']);
        $this->assertCount(1, $project->details['challenges']);
        $this->assertTrue($project->hasDetailPage());

        $response = $this->get('/projetos/projeto-completo')->assertOk();
        $response->assertSeeInOrder(['Visão geral', 'Objetivo', 'Tecnologias', 'Funcionalidades', 'Arquitetura', 'Screenshots', 'Desenvolvimento e desafios', 'Conclusão']);
        $response->assertSee('Segundo parágrafo.')->assertSee('Provar o fluxo ponta a ponta.')->assertSee('Feature B')->assertSee('Monólito em camadas.')->assertSee('Solução real.');
        $response->assertSee('<title>Projeto Completo — Projetos</title>', false);
    }

    public function test_a_project_without_content_has_no_detail_page_but_still_has_its_card(): void
    {
        $this->post('/admin/projetos', ['title' => 'Só o card', 'description' => 'Descrição.', 'is_active' => '1']);

        $project = Project::firstOrFail();
        $this->assertNull($project->details);
        $this->assertFalse($project->hasDetailPage());

        $this->get('/projetos')->assertOk()->assertSee('Só o card')->assertDontSee('Ver detalhes');
        $this->get('/projetos/'.$project->slug)->assertNotFound();
    }

    public function test_editing_keeps_the_existing_details_that_the_form_sends_back(): void
    {
        $this->post('/admin/projetos', $this->payload());
        $project = Project::firstOrFail();

        $this->put("/admin/projetos/{$project->id}", $this->payload(['title' => 'Renomeado', 'features' => "Só uma\nOutra"]))->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame('Renomeado', $project->title);
        $this->assertSame('projeto-completo', $project->slug);
        $this->assertSame(['Só uma', 'Outra'], $project->details['features']);
        $this->assertSame('Monólito em camadas.', $project->details['architecture']);
    }

    public function test_extra_sections_json_is_stored_validated_and_rendered_with_the_flow_diagram(): void
    {
        $sections = json_encode([['title' => 'Pipeline', 'paragraphs' => ['Texto.'], 'flow' => ['steps' => [['label' => 'A'], ['label' => 'B', 'status' => 'planned']]]]]);

        $this->post('/admin/projetos', $this->payload(['extra_sections' => $sections]))->assertSessionHasNoErrors();

        $response = $this->get('/projetos/projeto-completo')->assertOk()->assertSee('Pipeline');
        $this->assertSame(2, substr_count($response->getContent(), 'data-flow-step'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-flow-planned'));
    }

    public function test_invalid_extra_sections_are_rejected_with_a_clear_message(): void
    {
        foreach (['{"não é lista": 1}', 'isso não é json', '[{"paragraphs":["sem título"]}]', '[{"title":"X","items":"não-lista"}]'] as $json) {
            $this->post('/admin/projetos', $this->payload(['extra_sections' => $json]))->assertSessionHasErrors('extra_sections');
        }

        $this->assertSame(0, Project::count());
    }

    public function test_slug_is_normalized_and_must_be_unique_even_against_trashed_projects(): void
    {
        $this->post('/admin/projetos', $this->payload(['slug' => 'Meu Slug Único!']));
        $this->assertSame('meu-slug-unico', Project::firstOrFail()->slug);

        $this->post('/admin/projetos', $this->payload(['title' => 'Outro', 'slug' => 'meu-slug-unico']))->assertSessionHasErrors('slug');

        Project::firstOrFail()->delete();
        $this->post('/admin/projetos', $this->payload(['title' => 'Outro', 'slug' => 'meu-slug-unico']))->assertSessionHasErrors('slug');

        $this->post('/admin/projetos', $this->payload(['title' => 'Outro sem slug']));
        $this->assertSame('outro-sem-slug', Project::where('title', 'Outro sem slug')->firstOrFail()->slug);
    }

    public function test_feature_and_unfeature_respect_the_configured_limit(): void
    {
        SiteContent::write(SiteContent::SETTINGS, ['projects_per_page' => 4, 'featured_projects_limit' => 2, 'track_page_views' => true]);
        [$a, $b, $c] = Project::factory()->count(3)->create();

        $this->patch("/admin/projetos/{$a->id}/destaque")->assertSessionHas('status', 'Projeto colocado em destaque.');
        $this->patch("/admin/projetos/{$b->id}/destaque")->assertSessionHas('status');
        $this->patch("/admin/projetos/{$c->id}/destaque")->assertSessionHasErrors('featured');

        $this->assertSame(2, Project::where('featured', true)->count());
        $this->assertFalse($c->refresh()->featured);

        $this->patch("/admin/projetos/{$a->id}/destaque")->assertSessionHas('status', 'Destaque removido.');
        $this->patch("/admin/projetos/{$c->id}/destaque")->assertSessionHas('status');
        $this->assertTrue($c->refresh()->featured);

        $this->assertDatabaseHas('audit_logs', ['action' => 'FEATURE', 'resource_id' => $a->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'UNFEATURE', 'resource_id' => $a->id]);
    }

    public function test_the_form_also_enforces_the_featured_limit_and_zero_means_unlimited(): void
    {
        SiteContent::write(SiteContent::SETTINGS, ['projects_per_page' => 4, 'featured_projects_limit' => 1, 'track_page_views' => true]);
        Project::factory()->featured()->create();

        $this->post('/admin/projetos', $this->payload(['featured' => '1']))->assertSessionHasErrors('featured');

        SiteContent::write(SiteContent::SETTINGS, ['projects_per_page' => 4, 'featured_projects_limit' => 0, 'track_page_views' => true]);
        $this->post('/admin/projetos', $this->payload(['featured' => '1']))->assertSessionHasNoErrors();
        $this->assertSame(2, Project::where('featured', true)->count());
    }

    public function test_featured_projects_lead_the_public_list_and_appear_on_the_home_section(): void
    {
        Project::factory()->create(['title' => 'Comum', 'sort_order' => 1]);
        $destaque = Project::factory()->create(['title' => 'Em Destaque Total', 'sort_order' => 2, 'featured' => false]);
        Http::fake(['api.github.com/*' => Http::response(['data' => ['user' => null]])]);

        $this->get('/')->assertOk()->assertDontSee('Projetos em destaque');

        $this->patch("/admin/projetos/{$destaque->id}/destaque");

        $this->get('/projetos')->assertOk()->assertSeeInOrder(['Em Destaque Total', 'Comum'])->assertSee('data-project-featured', false);
        $this->get('/')->assertOk()->assertSee('Projetos em destaque')->assertSee('Em Destaque Total')->assertDontSee('Comum');
    }

    public function test_publish_and_unpublish_control_public_visibility_and_the_preview_shows_drafts(): void
    {
        $this->post('/admin/projetos', $this->payload());
        $project = Project::firstOrFail();

        $this->get('/projetos')->assertSee('Projeto Completo');
        $this->get("/projetos/{$project->slug}")->assertOk();

        $this->patch("/admin/projetos/{$project->id}/publicar")->assertSessionHas('status', 'Projeto movido para rascunho.');

        $this->get('/projetos')->assertDontSee('Projeto Completo');
        $this->get("/projetos/{$project->slug}")->assertNotFound();
        $this->get("/admin/projetos/{$project->id}/visualizar")->assertOk()->assertSee('data-preview-banner', false)->assertSee('rascunho');

        $this->patch("/admin/projetos/{$project->id}/publicar")->assertSessionHas('status', 'Projeto publicado.');
        $this->get("/projetos/{$project->slug}")->assertOk()->assertDontSee('data-preview-banner', false);
    }

    public function test_preview_of_a_project_without_details_explains_why_it_cannot_be_shown(): void
    {
        $project = Project::factory()->create();

        $this->from('/admin/projetos')->get("/admin/projetos/{$project->id}/visualizar")->assertRedirect('/admin/projetos')->assertSessionHasErrors('preview');
    }

    public function test_list_search_status_and_featured_filters_and_sorting(): void
    {
        Project::factory()->create(['title' => 'Alfa Publicado', 'slug' => 'alfa']);
        Project::factory()->inactive()->create(['title' => 'Beta Rascunho']);
        Project::factory()->featured()->create(['title' => 'Gama Destaque']);
        Project::factory()->create(['title' => 'Delta Excluído'])->delete();

        $this->get('/admin/projetos?q=alfa')->assertOk()->assertSee('Alfa Publicado')->assertDontSee('Beta Rascunho');
        $this->get('/admin/projetos?status=draft')->assertOk()->assertSee('Beta Rascunho')->assertDontSee('Alfa Publicado');
        $this->get('/admin/projetos?status=published')->assertOk()->assertDontSee('Beta Rascunho');
        $this->get('/admin/projetos?status=featured')->assertOk()->assertSee('Gama Destaque')->assertDontSee('Alfa Publicado');
        $this->get('/admin/projetos?status=trashed')->assertOk()->assertSee('Delta Excluído')->assertSee('Restaurar')->assertDontSee('Alfa Publicado');
        $this->get('/admin/projetos?sort=title&direction=desc')->assertOk()->assertSeeInOrder(['Gama Destaque', 'Beta Rascunho', 'Alfa Publicado']);
        $this->get('/admin/projetos?q=zzzz')->assertOk()->assertSee('Nenhum projeto encontrado');
    }

    public function test_list_paginates_and_shows_view_counts_from_real_page_views(): void
    {
        $counted = Project::factory()->create(['title' => 'Contado']);
        Project::factory()->count(16)->create();
        PageView::insert(array_fill(0, 3, ['path' => '/projetos/contado', 'project_id' => $counted->id, 'created_at' => now()]));

        $this->get('/admin/projetos?q=Contado')->assertOk()->assertSee('data-project-row', false)->assertSeeInOrder(['Contado', '3']);
        $this->get('/admin/projetos')->assertOk()->assertSee('Página 1 de 2');
    }

    public function test_gallery_upload_edit_and_remove(): void
    {
        $this->post('/admin/projetos', $this->payload(['gallery_files' => [$this->png('a.png'), $this->png('b.png')]]));
        $project = Project::firstOrFail();

        $screenshots = $project->details['screenshots'];
        $this->assertCount(2, $screenshots);
        $this->assertStringStartsWith('storage/projects/gallery/', $screenshots[0]['src']);
        Storage::disk('public')->assertExists(substr($screenshots[0]['src'], strlen('storage/')));

        $this->get('/projetos/'.$project->slug)->assertOk()->assertDontSee('data-screenshots-empty', false)->assertSee(asset($screenshots[0]['src']), false);

        $payload = $this->payload(['screenshots' => [
            0 => ['src' => $screenshots[0]['src'], 'alt' => 'Nova alt', 'caption' => 'Nova legenda', 'remove' => '0'],
            1 => ['src' => $screenshots[1]['src'], 'alt' => 'x', 'caption' => '', 'remove' => '1'],
            2 => ['src' => 'image/qualquer/injetado.png', 'alt' => 'injetado', 'caption' => ''],
        ]]);
        $this->put("/admin/projetos/{$project->id}", $payload);

        $kept = $project->refresh()->details['screenshots'];
        $this->assertCount(1, $kept);
        $this->assertSame('Nova alt', $kept[0]['alt']);
        $this->assertSame('Nova legenda', $kept[0]['caption']);
        Storage::disk('public')->assertMissing(substr($screenshots[1]['src'], strlen('storage/')));
    }

    public function test_static_gallery_images_are_kept_and_never_deleted_from_public_image(): void
    {
        $project = Project::factory()->withDetails(['overview' => ['Texto.'], 'screenshots' => [['src' => 'image/projetos/organizze/contas.png', 'alt' => 'Contas']]])->create();

        $this->put("/admin/projetos/{$project->id}", $this->payload(['screenshots' => [0 => ['src' => 'image/projetos/organizze/contas.png', 'alt' => 'Contas', 'caption' => '', 'remove' => '1']]]));

        $this->assertFileExists(public_path('image/projetos/organizze/contas.png'));
    }

    public function test_seo_fields_are_optional_and_override_the_defaults_when_filled(): void
    {
        $this->post('/admin/projetos', $this->payload());
        $project = Project::firstOrFail();
        $this->get("/projetos/{$project->slug}")->assertSee('<title>Projeto Completo — Projetos</title>', false);

        $this->put("/admin/projetos/{$project->id}", $this->payload([
            'seo_title' => 'Título SEO do projeto', 'seo_description' => 'Meta descrição do projeto',
            'og_title' => 'OG do projeto', 'og_description' => 'OG descrição do projeto', 'og_image' => $this->png('og.png'),
        ]));

        $response = $this->get("/projetos/{$project->slug}")->assertOk();
        $response->assertSee('<title>Título SEO do projeto</title>', false);
        $response->assertSee('<meta name="description" content="Meta descrição do projeto">', false);
        $response->assertSee('<meta property="og:title" content="OG do projeto">', false);
        $response->assertSee('<meta property="og:description" content="OG descrição do projeto">', false);
        $response->assertSee('og:image', false);
        $response->assertSee('<link rel="canonical"', false);
        $this->assertStringStartsWith('storage/projects/og/', $project->refresh()->og_image);
    }

    public function test_validation_limits_and_the_typed_values_are_kept_on_error(): void
    {
        $this->from('/admin/projetos/criar')
            ->post('/admin/projetos', $this->payload(['title' => '', 'description' => str_repeat('a', 1001), 'seo_description' => str_repeat('a', 321)]))
            ->assertRedirect('/admin/projetos/criar')
            ->assertSessionHasErrors(['title', 'description', 'seo_description'])
            ->assertSessionHasInput('overview', "Primeiro parágrafo.\n\nSegundo parágrafo.");

        $this->assertSame(0, Project::count());
    }

    public function test_project_actions_are_recorded_in_the_audit_log_and_listed(): void
    {
        $this->post('/admin/projetos', $this->payload());
        $project = Project::firstOrFail();
        $this->put("/admin/projetos/{$project->id}", $this->payload(['title' => 'Editado', 'is_active' => '0']));
        $this->delete("/admin/projetos/{$project->id}");

        $actions = AuditLog::orderBy('id')->pluck('action')->map->value->all();
        $this->assertSame(['CREATE', 'UPDATE', 'UNPUBLISH', 'DELETE'], $actions);

        $this->get('/admin/logs')->assertOk()->assertSee('Carlos')->assertSee('DELETE')->assertSee('Editado');
        $this->assertSame(4, substr_count($this->get('/admin/logs')->getContent(), 'data-log-row'));
        $this->assertSame(1, substr_count($this->get('/admin/logs?action=CREATE')->getContent(), 'data-log-row'));
        $this->get('/admin/logs?resource=Projeto&q=Carlos')->assertOk()->assertSee('data-log-row', false);
    }

    public function test_changes_invalidate_the_cache_so_the_public_list_updates_immediately(): void
    {
        $this->post('/admin/projetos', $this->payload(['title' => 'Antes']));
        $project = Project::firstOrFail();
        $this->get('/projetos')->assertSee('Antes');

        $this->put("/admin/projetos/{$project->id}", $this->payload(['title' => 'Depois']));

        $this->get('/projetos')->assertSee('Depois')->assertDontSee('Antes');
    }

    public function test_actions_on_a_missing_or_already_deleted_item_go_back_to_the_list_with_a_notice(): void
    {
        $project = Project::factory()->create();
        $this->delete("/admin/projetos/{$project->id}")->assertRedirect(route('admin.projects.index'));

        // Segunda exclusão (clique duplo / lista desatualizada), item inexistente e ações em item excluído.
        $this->delete("/admin/projetos/{$project->id}")->assertRedirect('/admin/projetos')->assertSessionHasErrors('not_found');
        $this->delete('/admin/projetos/999999')->assertRedirect('/admin/projetos')->assertSessionHasErrors('not_found');
        $this->patch("/admin/projetos/{$project->id}/publicar")->assertRedirect('/admin/projetos')->assertSessionHasErrors('not_found');
        $this->patch("/admin/projetos/{$project->id}/destaque")->assertRedirect('/admin/projetos')->assertSessionHasErrors('not_found');
        $this->get("/admin/projetos/{$project->id}/editar")->assertRedirect('/admin/projetos')->assertSessionHasErrors('not_found');
        $this->delete('/admin/experiencia/999999')->assertRedirect('/admin/experiencia')->assertSessionHasErrors('not_found');

        $this->followingRedirects()->delete('/admin/projetos/999999')->assertOk()->assertSee('Este item não existe mais');

        // URLs que realmente não existem continuam sendo 404, e visitantes não são afetados.
        $this->get('/admin/nao-existe')->assertNotFound();
        auth()->logout();
        $this->delete('/admin/projetos/999999')->assertRedirect(route('admin.login'));
    }
}
