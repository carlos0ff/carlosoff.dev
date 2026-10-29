<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteContent;
use App\Models\Technology;
use App\Models\User;
use App\Services\PortfolioContent;
use Database\Seeders\PortfolioContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PortfolioContentSeeder::class);
        $this->actingAs(User::factory()->admin()->create(['name' => 'Carlos']));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function aboutPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'José Carlos', 'headline' => 'Novo título', 'subtitle' => 'Subtítulo', 'description' => 'Descrição em destaque.',
            'biography' => "Parágrafo 1.\n\nParágrafo 2.", 'quote' => 'Citação nova.', 'location' => 'Goiana, PE',
            'professional_status' => 'Disponível', 'email' => 'contato@example.com',
            'photo' => 'https://exemplo.dev/foto.jpg', 'resume' => 'https://exemplo.dev/cv.pdf',
            'links' => [['label' => 'GitHub', 'url' => 'https://github.com/carlos0ff'], ['label' => '', 'url' => '']],
        ], $overrides);
    }

    public function test_about_form_is_prefilled_with_the_migrated_content(): void
    {
        $this->get('/admin/sobre')->assertOk()
            ->assertSee(config('portfolio.about.headline'))
            ->assertSee('value="José Carlos"', false)
            ->assertSee(config('portfolio.about.quote'));
    }

    public function test_saving_the_about_page_updates_the_public_page_immediately(): void
    {
        $this->get('/sobre')->assertOk()->assertSee(config('portfolio.about.headline')); // aquece o cache

        $this->put('/admin/sobre', $this->aboutPayload())->assertRedirect(route('admin.about.edit'))->assertSessionHas('status');

        $this->get('/sobre')->assertOk()
            ->assertSee('Novo título')->assertSee('Subtítulo')->assertSee('Descrição em destaque.')
            ->assertSee('Parágrafo 1.')->assertSee('Parágrafo 2.')->assertSee('Citação nova.')
            ->assertSee('Goiana, PE')->assertSee('Disponível')->assertSee('mailto:contato@example.com', false)
            ->assertSee('href="https://github.com/carlos0ff"', false)
            ->assertSee('https://exemplo.dev/foto.jpg', false)
            ->assertDontSee(config('portfolio.about.headline'));

        $this->assertCount(1, SiteContent::read(SiteContent::ABOUT)['links']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'UPDATE', 'resource_type' => 'Sobre']);
    }

    public function test_about_photo_and_resume_uploads_replace_typed_values_and_can_be_removed(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        $this->put('/admin/sobre', $this->aboutPayload([
            'photo_file' => UploadedFile::fake()->createWithContent('foto.png', $png),
            'resume_file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $about = SiteContent::read(SiteContent::ABOUT);
        $this->assertStringStartsWith('storage/about/', $about['photo']);
        $this->assertStringStartsWith('storage/about/', $about['resume']);
        Storage::disk('public')->assertExists(substr($about['photo'], 8));
        Storage::disk('public')->assertExists(substr($about['resume'], 8));

        $this->put('/admin/sobre', $this->aboutPayload(['photo' => $about['photo'], 'resume' => $about['resume'], 'remove_photo' => '1']));

        $after = SiteContent::read(SiteContent::ABOUT);
        $this->assertNull($after['photo']);
        $this->assertSame($about['resume'], $after['resume']);
        Storage::disk('public')->assertMissing(substr($about['photo'], 8));
    }

    public function test_about_validation_rejects_bad_data(): void
    {
        $this->put('/admin/sobre', $this->aboutPayload(['name' => '', 'headline' => '', 'email' => 'nao-e-email', 'photo' => 'javascript:alert(1)', 'resume' => '../../etc/passwd']))
            ->assertSessionHasErrors(['name', 'headline', 'email', 'photo', 'resume']);

        $this->put('/admin/sobre', $this->aboutPayload(['resume' => 'docs/../../.env', 'photo' => 'storage/../secreto.png']))->assertSessionHasErrors(['resume', 'photo']);

        $this->put('/admin/sobre', $this->aboutPayload(['links' => [['label' => 'X', 'url' => 'javascript:alert(1)']]]))->assertSessionHasErrors('links.0.url');
        $this->put('/admin/sobre', $this->aboutPayload(['resume_file' => UploadedFile::fake()->create('cv.exe', 10)]))->assertSessionHasErrors('resume_file');
        $this->put('/admin/sobre', $this->aboutPayload(['photo_file' => UploadedFile::fake()->create('foto.svg', 10)]))->assertSessionHasErrors('photo_file');
    }

    public function test_settings_control_the_public_projects_per_page(): void
    {
        Project::factory()->count(5)->create();

        $this->get('/projetos')->assertOk()->assertSee('Próxima');

        $this->put('/admin/configuracoes', ['projects_per_page' => 10, 'featured_projects_limit' => 2, 'track_page_views' => '1'])
            ->assertRedirect(route('admin.settings.edit'))->assertSessionHas('status');

        $this->get('/projetos')->assertOk()->assertDontSee('Próxima');
        $this->assertDatabaseHas('audit_logs', ['resource_type' => 'Configurações']);
    }

    public function test_settings_validation(): void
    {
        $this->put('/admin/configuracoes', ['projects_per_page' => 0, 'featured_projects_limit' => 99])->assertSessionHasErrors(['projects_per_page', 'featured_projects_limit']);
        $this->put('/admin/configuracoes', ['projects_per_page' => 'abc', 'featured_projects_limit' => -1])->assertSessionHasErrors(['projects_per_page', 'featured_projects_limit']);
    }

    public function test_clear_cache_forgets_the_public_content_cache(): void
    {
        $this->get('/sobre');
        $this->assertTrue(Cache::has('portfolio.content.about'));

        $this->post('/admin/configuracoes/limpar-cache')->assertRedirect(route('admin.settings.edit'))->assertSessionHas('status');

        $this->assertFalse(Cache::has('portfolio.content.about'));
    }

    public function test_users_page_lists_creates_and_deletes_administrators(): void
    {
        $this->get('/admin/usuarios')->assertOk()->assertSee('Carlos')->assertSee('Você');

        $this->post('/admin/usuarios', ['name' => 'Outra Pessoa', 'email' => 'outra@example.com', 'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123'])
            ->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'outra@example.com')->firstOrFail();
        $this->assertTrue($created->is_admin);
        $this->assertNotSame('senha-forte-123', $created->password);

        $this->delete("/admin/usuarios/{$created->id}")->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($created);
        $this->assertDatabaseHas('audit_logs', ['resource_type' => 'Usuário', 'action' => 'DELETE']);
    }

    public function test_users_cannot_delete_themselves_or_the_last_admin_and_passwords_are_validated(): void
    {
        $me = auth()->user();

        $this->delete("/admin/usuarios/{$me->id}")->assertSessionHasErrors('user');
        $this->assertModelExists($me);

        $this->post('/admin/usuarios', ['name' => 'X', 'email' => $me->email, 'password' => 'curta', 'password_confirmation' => 'diferente'])->assertSessionHasErrors(['email', 'password']);

        $last = User::factory()->admin()->create();
        User::where('id', '!=', $last->id)->delete();
        $this->actingAs(User::factory()->create(['is_admin' => false]))->delete("/admin/usuarios/{$last->id}")->assertForbidden();
    }

    public function test_the_import_seeder_is_idempotent_and_never_overwrites_edits(): void
    {
        Experience::where('company', 'Future Data')->firstOrFail()->update(['role' => 'Cargo editado no painel']);
        $this->put('/admin/sobre', $this->aboutPayload());

        $this->seed(PortfolioContentSeeder::class);

        $this->assertSame(3, Experience::count());
        $this->assertSame(3, Education::count());
        $this->assertSame(10, Technology::count());
        $this->assertSame(4, Course::count());
        $this->assertSame('Cargo editado no painel', Experience::where('company', 'Future Data')->firstOrFail()->role);
        $this->assertSame('Novo título', SiteContent::read(SiteContent::ABOUT)['headline']);
    }

    public function test_imported_periods_and_content_are_identical_to_the_original_config(): void
    {
        $content = app(PortfolioContent::class);

        $this->assertSame(
            array_column(config('portfolio.experiences'), 'period'),
            array_column($content->experiences(), 'period'),
        );
        $this->assertSame(
            array_column(config('portfolio.education'), 'period'),
            array_column($content->education(), 'period'),
        );
        $this->assertSame(array_column(config('portfolio.stack'), 'name'), array_column($content->stack(), 'name'));
        $this->assertSame(array_column(config('portfolio.certifications'), 'title'), array_column($content->courses(), 'title'));
        $this->assertSame(array_column(config('portfolio.experiences'), 'description'), array_column($content->experiences(), 'description'));
        $this->assertSame(config('portfolio.about.paragraphs'), $content->about()['paragraphs']);
    }
}
