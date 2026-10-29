<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Education;
use App\Models\SiteContent;
use App\Models\Technology;
use Database\Seeders\PortfolioContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O conteúdo público vem do banco; o seeder importa o que antes vivia em config/portfolio.php.
        $this->seed(PortfolioContentSeeder::class);
    }

    private function updateAbout(array $values): void
    {
        SiteContent::write(SiteContent::ABOUT, array_merge(SiteContent::read(SiteContent::ABOUT), $values));
    }

    public function test_about_page_lists_the_education_in_the_experience_format(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('id="education"', false);
        $response->assertSee('Formação Acadêmica');
        $response->assertSee('experience-reveal', false);

        foreach (config('portfolio.education') as $institution) {
            $response->assertSee($institution['company']);
            $response->assertSee($institution['period']);
            $this->assertFileExists(public_path($institution['logo']));
        }
    }

    public function test_about_intro_uses_the_headline_paragraphs_and_quote_from_the_database(): void
    {
        $about = config('portfolio.about');

        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('id="about"', false);
        $response->assertSee($about['headline']);
        $response->assertSee('<blockquote', false);
        $response->assertSee('If you ever spot me in the wild');

        foreach ($about['paragraphs'] as $paragraph) {
            $response->assertSee($paragraph);
        }
    }

    public function test_about_page_reflects_changes_saved_in_the_database(): void
    {
        $this->updateAbout([
            'headline' => 'Novo título do Sobre',
            'subtitle' => 'Novo subtítulo',
            'biography' => "Primeiro parágrafo novo.\n\nSegundo parágrafo novo.",
            'quote' => 'Nova citação.',
            'location' => 'Goiana, PE',
            'professional_status' => 'Disponível',
            'links' => [['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/exemplo']],
        ]);

        $response = $this->get(route('about'))->assertOk();

        $response->assertSee('Novo título do Sobre');
        $response->assertSee('Novo subtítulo');
        $response->assertSee('Primeiro parágrafo novo.');
        $response->assertSee('Segundo parágrafo novo.');
        $response->assertSee('Nova citação.');
        $response->assertSee('data-about-location', false);
        $response->assertSee('data-about-status', false);
        $response->assertSee('href="https://linkedin.com/in/exemplo"', false);
        $response->assertDontSee('If you ever spot me in the wild');
    }

    public function test_about_seo_fields_are_used_when_filled(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('<title>Sobre | José Carlos</title>', false);

        $this->updateAbout(['seo_title' => 'Título SEO', 'seo_description' => 'Meta descrição', 'og_title' => 'OG título', 'og_description' => 'OG descrição']);

        $this->get(route('about'))->assertOk()
            ->assertSee('<title>Título SEO</title>', false)
            ->assertSee('<meta name="description" content="Meta descrição">', false)
            ->assertSee('<meta property="og:title" content="OG título">', false)
            ->assertSee('<meta property="og:description" content="OG descrição">', false);
    }

    public function test_about_intro_falls_back_without_photo_resume_and_email(): void
    {
        $this->updateAbout(['photo' => null, 'resume' => null, 'email' => null]);

        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('data-photo-fallback', false);
        $response->assertSee('data-resume-disabled', false);
        $response->assertDontSee('mailto:', false);
    }

    public function test_about_intro_shows_photo_resume_and_email_when_configured(): void
    {
        $this->updateAbout([
            'photo' => 'image/instituicoes/unipe.jpg',
            'resume' => 'https://exemplo.dev/curriculo.pdf',
            'email' => 'contato@example.com',
        ]);

        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertDontSee('data-photo-fallback', false);
        $response->assertDontSee('data-resume-disabled', false);
        $response->assertSee(asset('image/instituicoes/unipe.jpg'), false);
        $response->assertSee('href="https://exemplo.dev/curriculo.pdf"', false);
        $response->assertSee('mailto:contato@example.com', false);
    }

    public function test_resume_button_is_disabled_until_the_pdf_exists_in_public_docs(): void
    {
        $this->updateAbout(['resume' => 'docs/curriculo-de-teste.pdf']);
        $path = public_path('docs/curriculo-de-teste.pdf');
        $this->assertFileDoesNotExist($path);

        $this->get(route('about'))->assertOk()->assertSee('data-resume-disabled', false);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, '%PDF-1.4 teste');

        try {
            $this->get(route('about'))
                ->assertOk()
                ->assertDontSee('data-resume-disabled', false)
                ->assertSee('href="'.asset('docs/curriculo-de-teste.pdf').'"', false);
        } finally {
            File::delete($path);
        }
    }

    public function test_stack_section_renders_every_technology_with_its_icon(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('id="stack"', false);

        foreach (config('portfolio.stack') as $technology) {
            $response->assertSee($technology['name']);
            $this->assertFileExists(public_path($technology['icon']));
        }
    }

    public function test_stack_items_without_an_icon_use_the_default_icon(): void
    {
        Course::query()->delete();
        Technology::query()->delete();
        Technology::factory()->create(['name' => 'Sem ícone', 'icon' => null]);
        Technology::factory()->create(['name' => 'Com ícone', 'icon' => 'image/stack/php.svg']);

        $content = $this->get(route('about'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'data-default-icon'));
    }

    public function test_draft_technologies_do_not_appear_on_the_public_stack(): void
    {
        Technology::factory()->create(['name' => 'Tecnologia rascunho', 'is_published' => false]);

        $this->get(route('about'))->assertOk()->assertDontSee('Tecnologia rascunho');
    }

    public function test_configured_certifications_render_in_pairs_and_their_files_exist(): void
    {
        $certifications = config('portfolio.certifications');

        $response = $this->get(route('about'));

        $response->assertOk();
        $this->assertCount(4, $certifications);
        $this->assertSame(count($certifications), substr_count($response->getContent(), 'data-certificate-open'));

        $response->assertSee('Cursos e certificados que complementam minha formação.');

        foreach ($certifications as $certification) {
            $response->assertSee($certification['title']);
            $response->assertSee($certification['description']);
            $this->assertFileExists(public_path($certification['file']));
        }
    }

    public function test_certifications_section_shows_an_empty_state_without_items(): void
    {
        Course::query()->delete();

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Cursos e Certificações')
            ->assertSee('Seus cursos e certificações aparecerão aqui.')
            ->assertDontSee('data-certificate-open', false);
    }

    public function test_certifications_open_the_modal_only_when_a_file_is_configured(): void
    {
        Course::query()->delete();
        Course::factory()->create(['name' => 'Curso com arquivo', 'institution' => 'Instituição A', 'completed_at' => '2026-03-10', 'image' => 'image/certificados/a.pdf']);
        Course::factory()->create(['name' => 'Curso sem arquivo', 'institution' => 'Instituição B', 'image' => null]);

        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('id="certificate-modal"', false);
        $response->assertSee('Curso com arquivo');
        $response->assertSee('Curso sem arquivo');
        $response->assertSee('Mar 2026');
        $response->assertSee('data-type="pdf"', false);
        $response->assertSee(asset('image/certificados/a.pdf'), false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-certificate-open'));
    }

    public function test_certifications_without_a_logo_get_the_default_icon(): void
    {
        Course::query()->delete();
        Course::factory()->create(['name' => 'Sem logo', 'institution' => 'Instituição A', 'image' => 'image/certificados/a.pdf', 'logo' => null]);
        Course::factory()->create(['name' => 'Com logo', 'institution' => 'Instituição B', 'image' => null, 'logo' => 'image/instituicoes/unipe.jpg']);

        $content = $this->get(route('about'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'data-default-icon'));
        $this->assertStringContainsString(asset('image/instituicoes/unipe.jpg'), $content);
    }

    public function test_certificate_url_and_extra_details_are_shown(): void
    {
        Course::query()->delete();
        Course::factory()->create([
            'name' => 'Curso completo', 'institution' => 'Instituição', 'type' => 'workshop', 'workload_hours' => 20,
            'certificate_url' => 'https://exemplo.dev/certificado', 'credential' => 'ABC-123', 'image' => null,
        ]);

        $this->get(route('about'))->assertOk()
            ->assertSee('Workshop')
            ->assertSee('20 h')
            ->assertSee('ABC-123')
            ->assertSee('href="https://exemplo.dev/certificado"', false);
    }

    public function test_education_periods_keep_the_forecast_end_date_and_notes(): void
    {
        Education::query()->delete();
        Education::factory()->create(['course' => 'Curso X', 'institution' => 'Inst', 'start_date' => '2024-02-01', 'end_date' => '2027-12-01', 'in_progress' => true, 'period_note' => null]);

        $this->get(route('about'))->assertOk()->assertSee('Fev 2024 – Dez 2027');
    }
}
