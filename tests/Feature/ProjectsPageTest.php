<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_projects_are_listed_and_github_is_not_used(): void
    {
        Http::fake();
        Project::factory()->create(['title' => 'Projeto Ativo']);
        Project::factory()->inactive()->create(['title' => 'Projeto Inativo']);

        $this->get('/projetos')
            ->assertOk()
            ->assertSee('Projeto Ativo')
            ->assertDontSee('Projeto Inativo');

        Http::assertNothingSent();
    }

    public function test_featured_projects_come_first_and_then_sort_order(): void
    {
        Project::factory()->create(['title' => 'Terceiro', 'sort_order' => 3]);
        Project::factory()->create(['title' => 'Primeiro comum', 'sort_order' => 1]);
        Project::factory()->featured()->create(['title' => 'Destaque', 'sort_order' => 9]);

        $this->get('/projetos')->assertSeeInOrder(['Destaque', 'Primeiro comum', 'Terceiro']);
    }

    public function test_technology_tags_are_rendered(): void
    {
        Project::factory()->create(['technologies' => ['Laravel', 'Vue 3', 'PostgreSQL']]);

        $this->get('/projetos')
            ->assertSee('Laravel')
            ->assertSee('Vue 3')
            ->assertSee('PostgreSQL');
    }

    public function test_github_and_demo_links_only_appear_when_present(): void
    {
        Project::factory()->create(['title' => 'Sem links']);

        $without = $this->get('/projetos')->assertOk();
        $without->assertDontSee('GitHub');
        $without->assertDontSee('Ver projeto');

        Project::factory()->create([
            'title' => 'Com links',
            'github_url' => 'https://github.com/exemplo/repo',
            'demo_url' => 'https://exemplo.dev/demo',
        ]);

        $with = $this->get('/projetos')->assertOk();
        $with->assertSee('href="https://github.com/exemplo/repo"', false);
        $with->assertSee('href="https://exemplo.dev/demo"', false);
        $with->assertSee('target="_blank" rel="noopener noreferrer"', false);
        $with->assertSee('GitHub');
        $with->assertSee('Ver projeto');
    }

    public function test_projects_page_works_without_active_projects(): void
    {
        Project::factory()->inactive()->create();

        $this->get('/projetos')
            ->assertOk()
            ->assertSee('data-projects-empty', false)
            ->assertSee('Projetos');
    }

    public function test_static_images_use_the_public_asset_and_projects_without_image_get_a_fallback(): void
    {
        Project::factory()->create(['title' => 'Realizze', 'image' => 'image/projetos/organizze/Dashboard.png', 'sort_order' => 1]);
        Project::factory()->create(['title' => 'WorkFacilit', 'image' => null, 'sort_order' => 2]);

        $response = $this->get('/projetos')->assertOk();

        $response->assertSee(asset('image/projetos/organizze/Dashboard.png'), false);
        $response->assertSee('data-project-fallback', false);
        $response->assertSee('WF');
        $this->assertFileExists(public_path('image/projetos/organizze/Dashboard.png'));
    }

    public function test_cards_have_a_reveal_stagger_index(): void
    {
        Project::factory()->count(2)->create();

        $this->get('/projetos')
            ->assertSee('experience-reveal', false)
            ->assertSee('--reveal-index: 0', false)
            ->assertSee('--reveal-index: 1', false);
    }
}
