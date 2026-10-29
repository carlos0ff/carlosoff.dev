<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CRUD, publicação, ordem, lixeira e reflexo público das quatro áreas gerenciadas pelo ResourceController.
 */
class ContentResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create(['name' => 'Carlos']));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resources(): array
    {
        return array_map('array_values', [
            'experiência' => [
                'uri' => '/admin/experiencia', 'model' => Experience::class, 'route' => 'admin.experiences',
                'payload' => ['role' => 'Dev Backend', 'company' => 'Empresa X', 'location' => 'Recife, PE', 'start_date' => '2025-01-01', 'is_current' => '1', 'technologies' => "PHP\nLaravel", 'responsibilities' => "APIs\nTestes", 'results' => 'Menos bugs', 'description' => 'Trabalho com APIs.', 'is_published' => '1'],
                'update' => ['role' => 'Dev Sênior'],
                'invalid' => ['role' => '', 'company' => ''],
                'label' => 'Dev Backend', 'updatedLabel' => 'Dev Sênior', 'public' => '/experiencia', 'searchColumn' => 'role',
            ],
            'formação' => [
                'uri' => '/admin/formacao', 'model' => Education::class, 'route' => 'admin.education',
                'payload' => ['course' => 'Ciência da Computação', 'institution' => 'Instituto Z', 'degree' => 'Bacharelado', 'start_date' => '2024-02-01', 'end_date' => '2027-12-01', 'in_progress' => '1', 'skills' => "Grafos\nBanco de dados", 'is_published' => '1'],
                'update' => ['course' => 'Sistemas de Informação'],
                'invalid' => ['course' => '', 'institution' => ''],
                'label' => 'Ciência da Computação', 'updatedLabel' => 'Sistemas de Informação', 'public' => '/sobre', 'searchColumn' => 'course',
            ],
            'stack' => [
                'uri' => '/admin/stack', 'model' => Technology::class, 'route' => 'admin.technologies',
                'payload' => ['name' => 'Spring Boot', 'category' => 'frameworks', 'official_url' => 'https://spring.io', 'featured' => '1', 'is_published' => '1'],
                'update' => ['name' => 'Neo4j', 'category' => 'database'],
                'invalid' => ['name' => '', 'category' => 'invalida'],
                'label' => 'Spring Boot', 'updatedLabel' => 'Neo4j', 'public' => '/sobre', 'searchColumn' => 'name',
            ],
            'cursos' => [
                'uri' => '/admin/cursos-certificacoes', 'model' => Course::class, 'route' => 'admin.courses',
                'payload' => ['name' => 'Curso de Docker', 'institution' => 'Plataforma W', 'type' => 'bootcamp', 'workload_hours' => '40', 'completed_at' => '2026-05-10', 'certificate_url' => 'https://exemplo.dev/c/1', 'credential' => 'DOC-1', 'is_published' => '1'],
                'update' => ['name' => 'Curso de Kubernetes'],
                'invalid' => ['name' => '', 'type' => 'inexistente'],
                'label' => 'Curso de Docker', 'updatedLabel' => 'Curso de Kubernetes', 'public' => '/sobre', 'searchColumn' => 'name',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function record(array $config): Experience|Education|Technology|Course
    {
        return $config['model']::firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    #[DataProvider('resources')]
    public function test_it_creates_an_item_reflected_on_the_public_page_and_audited(string $uri, string $model, string $route, array $payload, array $update, array $invalid, string $label, string $updatedLabel, string $public): void
    {
        $this->post($uri, $payload)->assertRedirect(route("{$route}.index"))->assertSessionHas('status');

        $item = $model::firstOrFail();
        $this->assertTrue($item->is_published);
        $this->assertSame(1, $item->sort_order);

        $this->get($uri)->assertOk()->assertSee($label);
        $this->get('/')->assertOk();
        $this->get($public)->assertOk()->assertSee($label);

        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE', 'user_name' => 'Carlos', 'resource_id' => $item->id]);
    }

    #[DataProvider('resources')]
    public function test_it_updates_an_item_and_the_public_page_follows_after_the_cache_is_warm(string $uri, string $model, string $route, array $payload, array $update, array $invalid, string $label, string $updatedLabel, string $public): void
    {
        $this->post($uri, $payload);
        $item = $model::firstOrFail();

        $this->get($public)->assertOk()->assertSee($label); // aquece o cache

        $this->put("{$uri}/{$item->id}", array_merge($payload, $update))->assertRedirect(route("{$route}.index"));

        $this->get($public)->assertOk()->assertSee($updatedLabel)->assertDontSee($label);
        $this->assertDatabaseHas('audit_logs', ['action' => 'UPDATE', 'resource_id' => $item->id]);
    }

    #[DataProvider('resources')]
    public function test_it_validates_required_fields_and_keeps_the_typed_data(string $uri, string $model, string $route, array $payload, array $update, array $invalid): void
    {
        $this->post($uri, array_merge($payload, $invalid))->assertSessionHasErrors(array_keys($invalid));
        $this->assertSame(0, $model::count());

        $response = $this->from("{$uri}/criar")->post($uri, array_merge($payload, $invalid));
        $response->assertRedirect("{$uri}/criar");
    }

    #[DataProvider('resources')]
    public function test_publish_and_unpublish_control_public_visibility(string $uri, string $model, string $route, array $payload, array $update, array $invalid, string $label, string $updatedLabel, string $public): void
    {
        $this->post($uri, $payload);
        $item = $model::firstOrFail();

        $this->get($public)->assertSee($label);

        $this->patch("{$uri}/{$item->id}/publicar")->assertRedirect()->assertSessionHas('status');
        $this->assertFalse($item->refresh()->is_published);
        $this->get($public)->assertOk()->assertDontSee($label);
        $this->assertDatabaseHas('audit_logs', ['action' => 'UNPUBLISH', 'resource_id' => $item->id]);

        $this->patch("{$uri}/{$item->id}/publicar");
        $this->assertTrue($item->refresh()->is_published);
        $this->get($public)->assertOk()->assertSee($label);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PUBLISH', 'resource_id' => $item->id]);
    }

    #[DataProvider('resources')]
    public function test_delete_is_soft_hides_the_item_publicly_and_can_be_restored(string $uri, string $model, string $route, array $payload, array $update, array $invalid, string $label, string $updatedLabel, string $public): void
    {
        $this->post($uri, $payload);
        $item = $model::firstOrFail();
        $this->get($public)->assertSee($label);

        $this->delete("{$uri}/{$item->id}")->assertRedirect(route("{$route}.index"));
        $this->assertSoftDeleted($item);
        $this->get($public)->assertOk()->assertDontSee($label);
        $this->get("{$uri}?status=trashed")->assertOk()->assertSee($label)->assertSee('Restaurar');
        $this->assertDatabaseHas('audit_logs', ['action' => 'DELETE', 'resource_id' => $item->id]);

        $this->patch("{$uri}/{$item->id}/restaurar")->assertRedirect();
        $this->assertNotSoftDeleted($item);
        $this->get($public)->assertOk()->assertSee($label);
        $this->assertDatabaseHas('audit_logs', ['action' => 'RESTORE', 'resource_id' => $item->id]);
    }

    #[DataProvider('resources')]
    public function test_move_reorders_items_and_renumbers_them(string $uri, string $model, string $route, array $payload): void
    {
        $factory = $model::factory();
        $a = $factory->create(['sort_order' => 1]);
        $b = $model::factory()->create(['sort_order' => 2]);
        $c = $model::factory()->create(['sort_order' => 5]);

        $this->patch("{$uri}/{$c->id}/mover", ['direction' => 'up'])->assertRedirect();
        $this->assertSame([$a->id, $c->id, $b->id], $model::ordered()->pluck('id')->all());
        $this->assertSame([1, 2, 3], $model::ordered()->pluck('sort_order')->all());

        $this->patch("{$uri}/{$a->id}/mover", ['direction' => 'up']); // já é o primeiro
        $this->assertSame([$a->id, $c->id, $b->id], $model::ordered()->pluck('id')->all());

        $this->patch("{$uri}/{$a->id}/mover", ['direction' => 'diagonal'])->assertSessionHasErrors('direction');
    }

    #[DataProvider('resources')]
    public function test_list_supports_search_status_filter_and_pagination(string $uri, string $model, string $route, array $payload, array $update, array $invalid, string $label, string $updatedLabel, string $public, string $searchColumn): void
    {
        $model::factory()->create([$searchColumn => 'Alvo Especial Buscado', 'is_published' => true]);
        $model::factory()->create([$searchColumn => 'Item de rascunho', 'is_published' => false]);
        $model::factory()->count(16)->create();

        $this->get($uri.'?q=Alvo+Especial')->assertOk()->assertSee('Alvo Especial Buscado')->assertDontSee('Item de rascunho');
        $this->get($uri.'?status=draft')->assertOk()->assertSee('Item de rascunho')->assertDontSee('Alvo Especial Buscado');
        $this->get($uri.'?status=published')->assertOk()->assertDontSee('Item de rascunho');
        $this->get($uri)->assertOk()->assertSee('Página 1 de 2');
        $this->get($uri.'?page=2')->assertOk()->assertSee('Página 2 de 2');
        $this->get($uri.'?q=nenhum-resultado-aqui')->assertOk()->assertSee('Nada encontrado');
    }

    #[DataProvider('resources')]
    public function test_empty_list_shows_an_empty_state_and_the_create_form_renders(string $uri): void
    {
        $this->get($uri)->assertOk()->assertSee('Nada cadastrado ainda');
        $this->get("{$uri}/criar")->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('name="_token"', false);
    }

    public function test_edit_form_is_prefilled_and_dates_lines_and_flags_roundtrip(): void
    {
        $uri = '/admin/experiencia';
        $this->post($uri, self::resources()['experiência'][3]);
        $item = Experience::firstOrFail();

        $this->assertSame(['PHP', 'Laravel'], $item->technologies);
        $this->assertSame(['APIs', 'Testes'], $item->responsibilities);
        $this->assertSame('2025-01-01', $item->start_date->toDateString());
        $this->assertTrue($item->is_current);

        $this->get("{$uri}/{$item->id}/editar")->assertOk()
            ->assertSee('value="Dev Backend"', false)
            ->assertSee('value="2025-01-01"', false)
            ->assertSee("PHP\nLaravel", false);
    }

    public function test_end_date_cannot_be_before_the_start_date(): void
    {
        $this->post('/admin/experiencia', ['role' => 'A', 'company' => 'B', 'start_date' => '2026-05-01', 'end_date' => '2025-01-01'])->assertSessionHasErrors('end_date');
    }

    public function test_logo_upload_is_validated_stored_and_replaced_without_touching_static_files(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $file = fn (string $name) => UploadedFile::fake()->createWithContent($name, $png);

        $this->post('/admin/experiencia', ['role' => 'A', 'company' => 'B', 'logo' => $file('logo.png')])->assertSessionHas('status');
        $experience = Experience::firstOrFail();
        $this->assertStringStartsWith('storage/experiences/', $experience->logo);
        Storage::disk('public')->assertExists(substr($experience->logo, strlen('storage/')));

        $old = $experience->logo;
        $this->put("/admin/experiencia/{$experience->id}", ['role' => 'A', 'company' => 'B', 'logo' => $file('novo.png')]);
        Storage::disk('public')->assertMissing(substr($old, strlen('storage/')));

        $static = Experience::factory()->create(['logo' => 'image/empresas/tse.jpg']);
        $this->put("/admin/experiencia/{$static->id}", ['role' => 'A', 'company' => 'B', 'remove_logo' => '1']);
        $this->assertNull($static->refresh()->logo);
        $this->assertFileExists(public_path('image/empresas/tse.jpg'));
    }

    public function test_dangerous_uploads_are_rejected(): void
    {
        foreach (['shell.php', 'icone.svg', 'animado.gif', 'doc.pdf'] as $name) {
            $this->post('/admin/experiencia', ['role' => 'A', 'company' => 'B', 'logo' => UploadedFile::fake()->create($name, 5)])->assertSessionHasErrors('logo');
        }

        $this->post('/admin/experiencia', ['role' => 'A', 'company' => 'B', 'logo' => UploadedFile::fake()->create('grande.png', 3000, 'image/png')])->assertSessionHasErrors('logo');
        $this->assertSame(0, Experience::count());
    }

    public function test_technology_slug_is_generated_unique_and_validated(): void
    {
        $this->post('/admin/stack', ['name' => 'Vue.js', 'category' => 'frontend']);
        $this->post('/admin/stack', ['name' => 'Vue.js', 'category' => 'frontend']);

        $this->assertSame(['vuejs', 'vuejs-2'], Technology::orderBy('id')->pluck('slug')->all());

        $this->post('/admin/stack', ['name' => 'Outro', 'category' => 'tools', 'slug' => 'vuejs'])->assertSessionHasErrors('slug');
        $this->post('/admin/stack', ['name' => 'Outro', 'category' => 'tools', 'slug' => 'Slug Inválido!'])->assertSessionHasErrors('slug');
    }

    public function test_technology_real_only_fields_stay_empty_by_default(): void
    {
        $this->post('/admin/stack', ['name' => 'Git', 'category' => 'tools']);

        $technology = Technology::firstOrFail();
        $this->assertNull($technology->years_experience);
        $this->assertNull($technology->level);
        $this->assertSame('tools', $technology->category->value);
    }

    public function test_course_dates_type_and_public_card_details(): void
    {
        $this->post('/admin/cursos-certificacoes', self::resources()['cursos'][3]);

        $course = Course::firstOrFail();
        $this->assertSame('bootcamp', $course->type->value);
        $this->assertSame(40, $course->workload_hours);

        $this->get('/sobre')->assertSee('Bootcamp')->assertSee('40 h')->assertSee('DOC-1')->assertSee('Mai 2026');
    }

    public function test_every_write_is_recorded_with_the_authenticated_user(): void
    {
        $this->post('/admin/experiencia', ['role' => 'A', 'company' => 'B']);

        $log = AuditLog::firstOrFail();
        $this->assertSame('Carlos', $log->user_name);
        $this->assertSame('Experiência', $log->resource_type);
        $this->assertSame('A — B', $log->resource_label);
    }
}
