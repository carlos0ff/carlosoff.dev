<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** PNG válido (sem depender da extensão GD) preenchido até o tamanho pedido em KB. */
    private function fakeImage(string $name, int $kilobytes = 10): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", $kilobytes * 1024));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['email' => 'admin@example.com']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Novo Projeto',
            'description' => 'Descrição do projeto.',
            'technologies' => ' Laravel ,, PHP , Vue 3 ,Laravel',
            'github_url' => 'https://github.com/exemplo/repo',
            'demo_url' => 'https://exemplo.dev',
            'featured' => '1',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/projetos')->assertRedirect(route('admin.login'));
        $this->get('/admin/projetos/criar')->assertRedirect(route('admin.login'));
        $this->post('/admin/projetos', $this->validPayload())->assertRedirect(route('admin.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->assertSame(0, Project::count());
    }

    public function test_login_page_is_visible_to_guests_and_redirects_authenticated_users(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Entrar no painel')->assertSee('type="password"', false);

        $this->actingAs($this->admin())->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_page_is_standalone_without_the_public_header_and_footer(): void
    {
        $response = $this->get('/admin/login')->assertOk();

        $response->assertSee('Painel Administrativo');
        $response->assertSee('noindex', false);
        $response->assertDontSee('<header', false);
        $response->assertDontSee('<footer', false);
        $response->assertDontSee('<nav', false);
        $response->assertDontSee('Built with');
        $response->assertDontSee('lang-switcher', false);
        $response->assertDontSee('image/texture.png', false);
    }

    public function test_login_errors_are_shown_inside_the_card(): void
    {
        $this->admin();

        $this->followingRedirects()
            ->from('/admin/login')
            ->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'errada'])
            ->assertSee('E-mail ou senha inválidos.')
            ->assertSee('border-error-border', false);
    }

    public function test_valid_credentials_log_the_admin_in(): void
    {
        $this->admin();

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->admin();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'errada'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'x@example.com', 'password' => 'errada']);
        }

        $this->post('/admin/login', ['email' => 'x@example.com', 'password' => 'errada'])->assertStatus(429);
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_there_is_no_public_registration_route(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_admin_can_create_a_project_with_an_uploaded_image(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projetos', $this->validPayload(['image' => $this->fakeImage('capa.jpg', 200)]))
            ->assertRedirect(route('admin.projects.index'))
            ->assertSessionHas('status');

        $project = Project::firstOrFail();
        $this->assertSame('Novo Projeto', $project->title);
        $this->assertSame(['Laravel', 'PHP', 'Vue 3'], $project->technologies);
        $this->assertTrue($project->featured);
        $this->assertTrue($project->is_active);
        $this->assertStringStartsWith('projects/', $project->image);
        Storage::disk('public')->assertExists($project->image);
        $this->assertSame(1, $project->sort_order);
    }

    public function test_new_projects_go_to_the_end_of_the_order_when_no_order_is_given(): void
    {
        Project::factory()->create(['sort_order' => 7]);

        $this->actingAs($this->admin())->post('/admin/projetos', $this->validPayload(['featured' => '0']));

        $this->assertSame(8, Project::where('title', 'Novo Projeto')->firstOrFail()->sort_order);
    }

    public function test_admin_can_edit_a_project(): void
    {
        $project = Project::factory()->create(['title' => 'Antigo', 'sort_order' => 4]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get("/admin/projetos/{$project->id}/editar")
            ->assertOk()
            ->assertSee('Antigo');

        $this->actingAs($admin)
            ->put("/admin/projetos/{$project->id}", $this->validPayload(['title' => 'Atualizado', 'is_active' => '0', 'featured' => '0', 'sort_order' => '2']))
            ->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame('Atualizado', $project->title);
        $this->assertFalse($project->is_active);
        $this->assertFalse($project->featured);
        $this->assertSame(2, $project->sort_order);
    }

    public function test_replacing_an_image_stores_the_new_one_and_removes_the_old_uploaded_file(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/projetos', $this->validPayload(['image' => $this->fakeImage('a.jpg')]));
        $project = Project::firstOrFail();
        $oldImage = $project->image;

        $this->actingAs($admin)->put("/admin/projetos/{$project->id}", $this->validPayload(['image' => $this->fakeImage('b.png')]));

        $project->refresh();
        $this->assertNotSame($oldImage, $project->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($project->image);
    }

    public function test_admin_can_remove_an_uploaded_image_without_deleting_the_project(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/projetos', $this->validPayload(['image' => $this->fakeImage('a.jpg')]));
        $project = Project::firstOrFail();
        $image = $project->image;

        $this->actingAs($admin)->put("/admin/projetos/{$project->id}", $this->validPayload(['remove_image' => '1']));

        $this->assertNull($project->refresh()->image);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_static_images_in_public_image_are_never_deleted(): void
    {
        $static = 'image/projetos/organizze/Dashboard.png';
        $project = Project::factory()->create(['image' => $static]);
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/projetos/{$project->id}", $this->validPayload(['image' => $this->fakeImage('novo.jpg')]));
        $this->assertFileExists(public_path($static));

        $other = Project::factory()->create(['image' => $static]);
        $this->actingAs($admin)->put("/admin/projetos/{$other->id}", $this->validPayload(['remove_image' => '1']));
        $this->assertNull($other->refresh()->image);
        $this->assertFileExists(public_path($static));

        $another = Project::factory()->create(['image' => $static]);
        $this->actingAs($admin)->delete("/admin/projetos/{$another->id}")->assertRedirect(route('admin.projects.index'));
        $this->assertSoftDeleted($another);
        $this->assertFileExists(public_path($static));
    }

    public function test_deleting_a_project_is_a_soft_delete_that_can_be_restored(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/projetos', $this->validPayload(['image' => $this->fakeImage('a.jpg')]));
        $project = Project::firstOrFail();
        $image = $project->image;

        $this->actingAs($admin)->delete("/admin/projetos/{$project->id}")->assertRedirect(route('admin.projects.index'));

        // Vai para a lixeira: some da lista pública, mas o registro e a imagem continuam para permitir restaurar.
        $this->assertSoftDeleted($project);
        Storage::disk('public')->assertExists($image);

        $this->actingAs($admin)->patch("/admin/projetos/{$project->id}/restaurar")->assertRedirect();
        $this->assertNotSoftDeleted($project);
    }

    public function test_toggle_flips_the_active_flag(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/projetos/{$project->id}/publicar")->assertRedirect()->assertSessionHas('status');
        $this->assertFalse($project->refresh()->is_active);

        $this->actingAs($admin)->patch("/admin/projetos/{$project->id}/publicar");
        $this->assertTrue($project->refresh()->is_active);
    }

    public function test_move_swaps_positions_and_keeps_the_order_consistent(): void
    {
        $a = Project::factory()->create(['title' => 'A', 'sort_order' => 10]);
        $b = Project::factory()->create(['title' => 'B', 'sort_order' => 20]);
        $c = Project::factory()->create(['title' => 'C', 'sort_order' => 20]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/projetos/{$c->id}/mover", ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['A', 'C', 'B'], Project::ordered()->pluck('title')->all());
        $this->assertSame([1, 2, 3], Project::ordered()->pluck('sort_order')->all());

        $this->actingAs($admin)->patch("/admin/projetos/{$a->id}/mover", ['direction' => 'down']);
        $this->assertSame(['C', 'A', 'B'], Project::ordered()->pluck('title')->all());

        $this->actingAs($admin)->patch("/admin/projetos/{$c->id}/mover", ['direction' => 'up']);
        $this->assertSame(['C', 'A', 'B'], Project::ordered()->pluck('title')->all());
        $this->assertSame(0, Project::where('sort_order', '<', 1)->count());

        $this->actingAs($admin)->patch("/admin/projetos/{$b->id}/mover", ['direction' => 'down']);
        $this->assertSame(['C', 'A', 'B'], Project::ordered()->pluck('title')->all());
    }

    public function test_move_never_crosses_between_featured_and_regular_projects(): void
    {
        $featured = Project::factory()->featured()->create(['title' => 'Destaque', 'sort_order' => 1]);
        $regular = Project::factory()->create(['title' => 'Comum', 'sort_order' => 2]);

        $this->actingAs($this->admin())->patch("/admin/projetos/{$regular->id}/mover", ['direction' => 'up']);

        $this->assertSame(['Destaque', 'Comum'], Project::ordered()->pluck('title')->all());
        $this->assertTrue($featured->refresh()->featured);
    }

    public function test_move_rejects_an_invalid_direction(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin())
            ->patch("/admin/projetos/{$project->id}/mover", ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    public function test_title_and_description_are_required(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projetos', ['title' => '', 'description' => ''])
            ->assertSessionHasErrors(['title', 'description']);

        $this->assertSame(0, Project::count());
    }

    public function test_urls_must_be_valid_http_urls(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projetos', $this->validPayload(['github_url' => 'isso-nao-e-url', 'demo_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors(['github_url', 'demo_url']);
    }

    public function test_images_larger_than_4mb_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projetos', $this->validPayload(['image' => $this->fakeImage('grande.jpg', 5000)]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Project::count());
    }

    public function test_invalid_image_extensions_are_rejected(): void
    {
        $admin = $this->admin();

        foreach (['animado.gif', 'documento.pdf', 'script.svg'] as $name) {
            $this->actingAs($admin)
                ->post('/admin/projetos', $this->validPayload(['image' => UploadedFile::fake()->create($name, 10)]))
                ->assertSessionHasErrors('image');
        }

        $this->assertSame(0, Project::count());
    }

    public function test_admin_list_shows_every_project_including_inactive_ones(): void
    {
        Project::factory()->create(['title' => 'Visível']);
        Project::factory()->inactive()->create(['title' => 'Escondido']);

        $this->actingAs($this->admin())
            ->get('/admin/projetos')
            ->assertOk()
            ->assertSee('Visível')
            ->assertSee('Escondido')
            ->assertSee('Despublicar')
            ->assertSee('Publicar');
    }
}
