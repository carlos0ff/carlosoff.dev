<?php

namespace Tests\Feature;

use App\Models\Experience;
use Database\Seeders\PortfolioContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExperiencePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PortfolioContentSeeder::class);

        Http::fake(['api.github.com/*' => Http::response(['data' => ['user' => null]])]);
    }

    public function test_home_shows_a_three_item_preview_with_link_to_the_full_page(): void
    {
        $experiences = config('portfolio.experiences');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="experience"', false);
        $response->assertSee('Work Experience');
        $response->assertSee('Ver todas as experiências');
        $response->assertSee('href="'.route('experience').'"', false);

        foreach (array_slice($experiences, 0, 3) as $experience) {
            $response->assertSee($experience['role']);
        }

        foreach (array_slice($experiences, 3) as $experience) {
            $response->assertDontSee($experience['role']);
        }
    }

    public function test_experience_page_lists_every_experience_from_the_same_source(): void
    {
        $response = $this->get('/experiencia');

        $response->assertOk();
        $response->assertSee('<title>Experiência Profissional | Carlos Off</title>', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertDontSee('Ver todas as experiências');

        foreach (config('portfolio.experiences') as $experience) {
            $response->assertSee($experience['company']);
            $response->assertSee($experience['role']);
        }
    }

    public function test_experience_page_reflects_changes_and_hides_drafts(): void
    {
        Experience::factory()->create(['role' => 'Cargo novo', 'company' => 'Empresa Nova', 'start_date' => '2026-01-01', 'is_current' => true, 'location' => 'Recife, PE', 'responsibilities' => ['Cuidar de servidores'], 'results' => ['Reduziu falhas em 30%'], 'sort_order' => 0]);
        Experience::factory()->create(['role' => 'Cargo rascunho', 'company' => 'Empresa Rascunho', 'is_published' => false]);

        $this->get('/experiencia')->assertOk()
            ->assertSee('Cargo novo')
            ->assertSee('Jan 2026 – Presente')
            ->assertSee('Recife, PE')
            ->assertSee('Cuidar de servidores')
            ->assertSee('Reduziu falhas em 30%')
            ->assertDontSee('Cargo rascunho');
    }

    public function test_public_order_follows_the_sort_order_set_in_the_admin(): void
    {
        // Primeira leitura aquece o cache; salvar pelo model precisa invalidá-lo.
        $this->get('/experiencia')->assertOk()->assertSeeInOrder(['Tribunal Superior Eleitoral', 'Future Data']);

        Experience::where('company', 'Future Data')->firstOrFail()->update(['sort_order' => 0]);

        $this->get('/experiencia')->assertOk()->assertSeeInOrder(['Future Data', 'Tribunal Superior Eleitoral', 'Fada Quality Service LTDA']);
    }

    public function test_every_configured_logo_exists_and_the_current_highlights_lead_the_home_preview(): void
    {
        $experiences = config('portfolio.experiences');

        foreach ($experiences as $experience) {
            if ($experience['logo']) {
                $this->assertFileExists(public_path($experience['logo']));
            }
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tribunal Superior Eleitoral')
            ->assertSee(asset('image/empresas/tse.jpg'), false);
    }
}
