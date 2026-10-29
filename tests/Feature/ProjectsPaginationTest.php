<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createProjects(int $count): void
    {
        foreach (range(1, $count) as $number) {
            Project::factory()->create(['title' => "Projeto {$number}", 'sort_order' => $number]);
        }
    }

    public function test_the_per_page_value_comes_from_a_single_config_entry(): void
    {
        $this->assertSame(4, config('portfolio.projects_per_page'));
    }

    public function test_the_first_page_shows_exactly_four_projects_and_the_controls(): void
    {
        $this->createProjects(9);

        $response = $this->get('/projetos')->assertOk();

        $response->assertSeeInOrder(['Projeto 1', 'Projeto 2', 'Projeto 3', 'Projeto 4']);
        $response->assertDontSee('Projeto 5');
        $response->assertSee('aria-label="Paginação dos projetos"', false);
        $response->assertSee('href="'.url('/projetos').'?page=2"', false);
        $response->assertSee('href="'.url('/projetos').'?page=3"', false);
        $this->assertSame(4, substr_count($response->getContent(), '<article'));
    }

    public function test_previous_is_disabled_on_the_first_page_and_the_current_page_is_highlighted(): void
    {
        $this->createProjects(9);

        $content = $this->get('/projetos')->getContent();

        $this->assertMatchesRegularExpression('/<span aria-disabled="true"[^>]*>Anterior<\/span>/', $content);
        $this->assertDoesNotMatchRegularExpression('/<span aria-disabled="true"[^>]*>Próxima<\/span>/', $content);
        $this->assertMatchesRegularExpression('/<span aria-current="page"[^>]*>1<\/span>/', $content);
    }

    public function test_the_second_page_shows_the_next_projects_with_both_controls_enabled(): void
    {
        $this->createProjects(9);

        $response = $this->get('/projetos?page=2')->assertOk();

        $response->assertSeeInOrder(['Projeto 5', 'Projeto 6', 'Projeto 7', 'Projeto 8']);
        $response->assertDontSee('Projeto 4');
        $response->assertDontSee('Projeto 9');
        $response->assertSee('rel="prev"', false);
        $response->assertSee('rel="next"', false);
        $this->assertMatchesRegularExpression('/<span aria-current="page"[^>]*>2<\/span>/', $response->getContent());
    }

    public function test_next_is_disabled_on_the_last_page(): void
    {
        $this->createProjects(9);

        $content = $this->get('/projetos?page=3')->assertOk()->assertSee('Projeto 9')->getContent();

        $this->assertMatchesRegularExpression('/<span aria-disabled="true"[^>]*>Próxima<\/span>/', $content);
        $this->assertStringContainsString('rel="prev"', $content);
        $this->assertStringNotContainsString('rel="next"', $content);
    }

    public function test_no_controls_are_shown_when_everything_fits_in_one_page(): void
    {
        $this->createProjects(4);

        $this->get('/projetos')
            ->assertOk()
            ->assertDontSee('Paginação dos projetos')
            ->assertSee('Projeto 4');
    }

    public function test_inactive_projects_do_not_count_towards_the_pages(): void
    {
        $this->createProjects(4);
        Project::factory()->inactive()->create(['title' => 'Escondido']);

        $this->get('/projetos?page=2')->assertOk()->assertDontSee('Escondido');
    }
}
