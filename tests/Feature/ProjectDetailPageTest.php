<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_get_a_unique_slug_when_created(): void
    {
        $first = Project::factory()->create(['title' => 'Meu Projeto']);
        $second = Project::factory()->create(['title' => 'Meu Projeto ']);

        $this->assertSame('meu-projeto', $first->slug);
        $this->assertSame('meu-projeto-2', $second->slug);
    }

    public function test_currencee_page_renders_every_section_from_the_database(): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get('/projetos/currencee')->assertOk();

        $response->assertSee('<h1', false);
        $response->assertSeeInOrder(['Visão geral', 'Tecnologias', 'Funcionalidades', 'Screenshots', 'Desenvolvimento e desafios', 'Conclusão']);
        $response->assertSee('Mais de 160 moedas disponíveis para conversão.');
        $response->assertSee('Compatibilidade com versões antigas do Safari');
        $response->assertSee('Autoprefixer');
        $response->assertSee('Vite');
        $response->assertSee('data-screenshots-empty', false);
        $response->assertSee('href="'.route('projects.index').'"', false);
    }

    public function test_currencee_page_has_seo_metadata_and_no_invented_external_links(): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get('/projetos/currencee')->assertOk();

        $response->assertSee('<title>Currencee — Projetos</title>', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertDontSee('og:image', false);
        $response->assertDontSee('victoreke', false);

        // O footer do site tem links externos; o conteúdo do projeto (sem URLs cadastradas) não pode ter nenhum.
        preg_match('/<article class="mx-auto.*?<\/article>/s', $response->getContent(), $article);
        $this->assertArrayHasKey(0, $article);
        $this->assertStringNotContainsString('target="_blank"', $article[0]);
        $this->assertStringNotContainsString('http', str_replace(url('/'), '', $article[0]));
    }

    public function test_cover_image_and_links_are_used_when_available(): void
    {
        $project = Project::factory()->withDetails()->create([
            'image' => 'image/projetos/organizze/Dashboard.png',
            'github_url' => 'https://github.com/exemplo/repo',
            'demo_url' => 'https://exemplo.dev',
        ]);

        $response = $this->get("/projetos/{$project->slug}")->assertOk();

        $response->assertSee('og:image', false);
        $response->assertSee(asset('image/projetos/organizze/Dashboard.png'), false);
        $response->assertSee('href="https://github.com/exemplo/repo"', false);
        $response->assertSee('href="https://exemplo.dev"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_screenshots_are_rendered_with_alt_text_when_configured(): void
    {
        $project = Project::factory()->withDetails([
            'overview' => ['Texto.'],
            'screenshots' => [['src' => 'image/projetos/organizze/contas.png', 'alt' => 'Tela de contas']],
        ])->create();

        $this->get("/projetos/{$project->slug}")
            ->assertOk()
            ->assertSee('alt="Tela de contas"', false)
            ->assertDontSee('data-screenshots-empty', false);
    }

    public function test_the_card_of_a_project_with_details_links_to_the_internal_page(): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get('/projetos')->assertOk();

        $response->assertSee('Currencee');
        $response->assertSee('Ver detalhes');

        foreach (['currencee', 'fintrack', 'workfacilit', 'vigiasus'] as $slug) {
            $response->assertSee('href="'.route('projects.show', $slug).'"', false);
        }

        $this->get('/projetos?page=2')->assertOk()
            ->assertSee('PicPay Wallet')
            ->assertSee('href="'.route('projects.show', 'picpay-wallet').'"', false);
    }

    public function test_cards_without_details_have_no_detail_link(): void
    {
        Project::factory()->create(['title' => 'Sem detalhes']);

        $this->get('/projetos')->assertOk()->assertDontSee('Ver detalhes')->assertDontSee('/projetos/sem-detalhes', false);
    }

    public function test_inactive_projects_and_projects_without_details_return_404(): void
    {
        $inactive = Project::factory()->withDetails()->inactive()->create();
        $plain = Project::factory()->create();

        $this->get("/projetos/{$inactive->slug}")->assertNotFound();
        $this->get("/projetos/{$plain->slug}")->assertNotFound();
        $this->get('/projetos/nao-existe')->assertNotFound();
    }
}
