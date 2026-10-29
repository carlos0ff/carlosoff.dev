<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function getRoutes(): array
    {
        $paths = [
            '/admin', '/admin/projetos', '/admin/projetos/criar', '/admin/experiencia', '/admin/experiencia/criar',
            '/admin/stack', '/admin/stack/criar', '/admin/cursos-certificacoes', '/admin/cursos-certificacoes/criar',
            '/admin/formacao', '/admin/formacao/criar', '/admin/sobre', '/admin/analytics', '/admin/analytics/paginas',
            '/admin/analytics/projetos', '/admin/logs', '/admin/usuarios', '/admin/configuracoes',
        ];

        return array_combine($paths, array_map(fn (string $path) => [$path], $paths));
    }

    #[DataProvider('getRoutes')]
    public function test_guests_are_redirected_to_login_on_every_admin_page(string $path): void
    {
        $this->get($path)->assertRedirect(route('admin.login'));
    }

    #[DataProvider('getRoutes')]
    public function test_users_without_admin_access_are_forbidden_on_every_admin_page(string $path): void
    {
        $this->actingAs(User::factory()->create())->get($path)->assertForbidden();
    }

    #[DataProvider('getRoutes')]
    public function test_admins_can_open_every_admin_page(string $path): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($path)->assertOk()->assertSee('noindex', false);
    }

    public function test_write_routes_are_protected_for_guests_and_non_admins(): void
    {
        $project = Project::factory()->create();
        $experience = Experience::factory()->create();
        $requests = [
            ['post', '/admin/projetos', ['title' => 'X', 'description' => 'Y']],
            ['delete', "/admin/projetos/{$project->id}", []],
            ['patch', "/admin/projetos/{$project->id}/destaque", []],
            ['post', '/admin/experiencia', ['role' => 'A', 'company' => 'B']],
            ['delete', "/admin/experiencia/{$experience->id}", []],
            ['put', '/admin/sobre', ['name' => 'X', 'headline' => 'Y']],
            ['put', '/admin/configuracoes', ['projects_per_page' => 2, 'featured_projects_limit' => 1]],
            ['post', '/admin/usuarios', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'senha-forte-1', 'password_confirmation' => 'senha-forte-1']],
        ];

        foreach ($requests as [$method, $uri, $data]) {
            $this->{$method}($uri, $data)->assertRedirect(route('admin.login'));
            $this->actingAs(User::factory()->create())->{$method}($uri, $data)->assertForbidden();
            auth()->logout();
        }

        $this->assertSame(1, Project::count());
        $this->assertSame(1, Experience::count());
    }

    public function test_a_non_admin_user_cannot_log_in_to_the_panel(): void
    {
        User::factory()->create(['email' => 'comum@example.com']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'comum@example.com', 'password' => 'password'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_old_english_admin_url_redirects_to_the_new_one(): void
    {
        $this->get('/admin/projects')->assertRedirect('/admin/projetos');
    }

    public function test_admin_pages_are_not_counted_and_public_pages_use_the_track_middleware(): void
    {
        $this->assertNotEmpty(Course::count() + Education::count() + Technology::count() + 1);
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();

        $this->assertDatabaseCount('page_views', 0);
    }
}
