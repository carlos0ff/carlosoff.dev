<?php

namespace Tests\Feature\Admin;

use App\Models\PageView;
use App\Models\Project;
use App\Models\SiteContent;
use App\Models\User;
use App\Services\Analytics;
use Database\Seeders\PortfolioContentSeeder;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    private const SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.github.com/*' => Http::response(['data' => ['user' => null]])]);
    }

    private function visit(string $path, string $userAgent = self::CHROME_DESKTOP, array $headers = []): void
    {
        $this->withHeaders(array_merge(['User-Agent' => $userAgent], $headers))->get($path);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordView(string $path, string $when = 'now', array $attributes = []): void
    {
        PageView::create(array_merge(['path' => $path, 'device' => 'desktop', 'browser' => 'Chrome', 'os' => 'Windows', 'ip_hash' => 'h'], $attributes))
            ->forceFill(['created_at' => now()->modify($when)])->save();
    }

    public function test_every_public_page_view_is_recorded_with_route_device_browser_and_os(): void
    {
        $this->seed(ProjectSeeder::class);

        foreach (['/', '/sobre', '/projetos', '/experiencia', '/contato', '/projetos/currencee'] as $path) {
            $this->visit($path);
        }

        $this->assertDatabaseCount('page_views', 6);

        $view = PageView::where('path', '/projetos')->firstOrFail();
        $this->assertSame('projects.index', $view->route_name);
        $this->assertSame('desktop', $view->device);
        $this->assertSame('Chrome', $view->browser);
        $this->assertSame('Windows', $view->os);
        $this->assertNotNull($view->created_at);

        $this->assertSame('/', PageView::where('route_name', 'home')->firstOrFail()->path);
    }

    public function test_a_project_page_view_is_linked_to_its_project(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->visit('/projetos/fintrack');

        $this->assertSame(Project::where('slug', 'fintrack')->firstOrFail()->id, PageView::firstOrFail()->project_id);
    }

    public function test_device_browser_and_os_are_detected_from_the_user_agent(): void
    {
        $this->visit('/contato', self::SAFARI_IPHONE);

        $view = PageView::firstOrFail();
        $this->assertSame('mobile', $view->device);
        $this->assertSame('Safari', $view->browser);
        $this->assertSame('iOS', $view->os);
    }

    public function test_no_personal_data_is_stored_the_ip_is_only_a_daily_hash(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->withHeaders(['User-Agent' => self::CHROME_DESKTOP])->get('/contato');

        $view = PageView::firstOrFail();
        $this->assertSame(64, strlen($view->ip_hash));
        $this->assertNotSame('203.0.113.7', $view->ip_hash);
        $this->assertStringNotContainsString('203.0.113.7', json_encode($view->getAttributes()));

        $this->travel(1)->day();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->withHeaders(['User-Agent' => self::CHROME_DESKTOP])->get('/contato');

        $this->assertSame(2, PageView::distinct()->count('ip_hash'), 'o hash muda todo dia para não rastrear a mesma pessoa');
    }

    public function test_only_the_referer_host_is_stored_and_never_for_internal_navigation(): void
    {
        $this->visit('/contato', self::CHROME_DESKTOP, ['Referer' => 'https://www.google.com/search?q=carlos+segredo']);
        $this->visit('/sobre', self::CHROME_DESKTOP, ['Referer' => 'http://localhost/contato']);

        $this->assertSame('www.google.com', PageView::where('path', '/contato')->firstOrFail()->referer_host);
        $this->assertNull(PageView::where('path', '/sobre')->firstOrFail()->referer_host);
        $this->assertStringNotContainsString('segredo', json_encode(PageView::all()->toArray()));
    }

    public function test_bots_admins_missing_pages_ajax_and_non_get_requests_are_not_recorded(): void
    {
        $this->visit('/contato', 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        $this->visit('/contato', 'curl/8.5.0');
        $this->visit('/contato', '');
        $this->visit('/projetos/nao-existe');
        $this->withHeaders(['User-Agent' => self::CHROME_DESKTOP, 'X-Requested-With' => 'XMLHttpRequest'])->get('/contato');
        $this->assertDatabaseCount('page_views', 0);

        $this->actingAs(User::factory()->admin()->create())->withHeaders(['User-Agent' => self::CHROME_DESKTOP])->get('/contato');
        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_tracking_can_be_turned_off_in_the_settings_and_never_breaks_the_page(): void
    {
        SiteContent::write(SiteContent::SETTINGS, ['projects_per_page' => 4, 'featured_projects_limit' => 3, 'track_page_views' => false]);

        $this->visit('/contato');
        $this->assertDatabaseCount('page_views', 0);

        SiteContent::write(SiteContent::SETTINGS, ['projects_per_page' => 4, 'featured_projects_limit' => 3, 'track_page_views' => true]);
        DB::statement('DROP TABLE page_views');

        $this->withHeaders(['User-Agent' => self::CHROME_DESKTOP])->get('/contato')->assertOk();
    }

    public function test_summary_counts_today_week_month_and_total(): void
    {
        $this->recordView('/a', 'now');
        $this->recordView('/a', '-2 days');
        $this->recordView('/b', '-10 days');
        $this->recordView('/b', '-45 days');

        $summary = app(Analytics::class)->summary();

        $this->assertSame(1, $summary['today']);
        $this->assertSame(2, $summary['week']);
        $this->assertSame(3, $summary['month']);
        $this->assertSame(4, $summary['total']);
    }

    public function test_top_pages_respect_the_selected_period(): void
    {
        foreach (range(1, 3) as $i) {
            $this->recordView('/projetos/currencee');
        }
        $this->recordView('/');
        $this->recordView('/', '-20 days');
        $this->recordView('/', '-40 days');
        $this->recordView('/experiencia', '-200 days');

        $analytics = app(Analytics::class);

        $this->assertSame(['/projetos/currencee' => 3, '/' => 1], $analytics->topPages('today')->pluck('views', 'path')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['/projetos/currencee' => 3, '/' => 2], $analytics->topPages('30d')->pluck('views', 'path')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(2, $analytics->topPages('90d')->count());
        $this->assertSame(3, $analytics->topPages('12m')->count());
        $this->assertSame(3, $analytics->topPages('all')->count());
        $this->assertSame(7, $analytics->totalInRange('all'));
        $this->assertSame(4, $analytics->totalInRange('today'));
    }

    public function test_top_projects_come_from_real_project_views(): void
    {
        $a = Project::factory()->create(['title' => 'Mais visto']);
        $b = Project::factory()->create(['title' => 'Menos visto']);
        Project::factory()->create(['title' => 'Sem visitas']);

        foreach (range(1, 3) as $i) {
            $this->recordView('/projetos/a', 'now', ['project_id' => $a->id]);
        }
        $this->recordView('/projetos/b', 'now', ['project_id' => $b->id]);
        $this->recordView('/projetos/b', '-100 days', ['project_id' => $b->id]);
        $this->recordView('/projetos');

        $analytics = app(Analytics::class);

        $this->assertSame(['Mais visto' => 3, 'Menos visto' => 1], $analytics->topProjects('30d')->pluck('views', 'title')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(2, (int) $analytics->topProjects('all')->firstWhere('title', 'Menos visto')->views);
        $this->assertSame([$a->id => 3, $b->id => 2], $analytics->viewsByProject([$a->id, $b->id]));
    }

    public function test_breakdowns_group_devices_browsers_and_referers(): void
    {
        $this->recordView('/a', 'now', ['device' => 'mobile', 'browser' => 'Safari', 'referer_host' => 'google.com']);
        $this->recordView('/a', 'now', ['device' => 'mobile', 'browser' => 'Safari']);
        $this->recordView('/a', 'now', ['device' => 'desktop', 'browser' => 'Chrome', 'referer_host' => 'google.com']);

        $analytics = app(Analytics::class);

        $this->assertSame(['mobile' => 2, 'desktop' => 1], $analytics->breakdown('device', 'today')->pluck('views', 'label')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['Safari' => 2, 'Chrome' => 1], $analytics->breakdown('browser', 'today')->pluck('views', 'label')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['google.com' => 2, 'Direto / desconhecido' => 1], $analytics->breakdown('referer_host', 'today')->pluck('views', 'label')->map(fn ($v) => (int) $v)->all());
    }

    public function test_series_fills_gaps_and_groups_by_day_week_and_month(): void
    {
        $this->recordView('/a', 'now');
        $this->recordView('/a', 'now');
        $this->recordView('/a', '-3 days');
        $this->recordView('/a', '-20 days');

        $analytics = app(Analytics::class);

        $daily = $analytics->series('7d', 'day');
        $this->assertCount(7, $daily['labels']);
        $this->assertSame(2, end($daily['values']));
        $this->assertSame(3, $daily['total']);
        $this->assertContains(0, $daily['values']);

        $this->assertSame(4, $analytics->series('30d', 'day')['total']);
        $this->assertSame(4, array_sum($analytics->series('30d', 'week')['values']));
        $this->assertLessThanOrEqual(6, count($analytics->series('30d', 'week')['values']));
        $this->assertSame(4, array_sum($analytics->series('12m', 'month')['values']));
        $this->assertCount(12, $analytics->series('12m', 'month')['values']);
        $this->assertSame(4, $analytics->series('all', 'month')['total']);
    }

    public function test_aggregation_runs_in_the_database_with_a_constant_number_of_queries(): void
    {
        foreach (range(1, 120) as $i) {
            $this->recordView('/p/'.($i % 7), '-'.($i % 40).' days');
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $analytics = app(Analytics::class);
        $analytics->summary();
        $analytics->series('90d', 'day');
        $analytics->topPages('90d');
        $analytics->topProjects('90d');
        $analytics->breakdown('device', '90d');

        $this->assertLessThanOrEqual(7, count(DB::getQueryLog()), 'nenhuma visualização individual é carregada no PHP');
    }

    public function test_admin_analytics_pages_render_real_data_and_the_period_filters(): void
    {
        $project = Project::factory()->create(['title' => 'Projeto Popular', 'slug' => 'popular']);
        foreach (range(1, 5) as $i) {
            $this->recordView('/projetos/popular', 'now', ['project_id' => $project->id, 'referer_host' => 'linkedin.com']);
        }
        $this->recordView('/sobre');
        $this->recordView('/velha', '-100 days');

        $this->actingAs(User::factory()->admin()->create());

        $overview = $this->get('/admin/analytics')->assertOk();
        $overview->assertSee('Acessos')->assertSee('linkedin.com')->assertSee('<svg', false)->assertSee('desktop')->assertSee('Chrome')->assertSee('Windows');

        $this->get('/admin/analytics/paginas?range=today')->assertOk()->assertSeeInOrder(['/projetos/popular', '/sobre'])->assertDontSee('/velha');
        $this->get('/admin/analytics/paginas?range=all')->assertOk()->assertSee('/velha');
        $this->get('/admin/analytics/projetos?range=30d')->assertOk()->assertSee('Projeto Popular')->assertSee('5');
        $this->get('/admin/analytics/paginas?range=invalido')->assertOk();

        foreach (['day', 'week', 'month'] as $granularity) {
            $this->get("/admin/analytics?granularity={$granularity}")->assertOk()->assertSee('aria-current', false);
        }
    }

    public function test_dashboard_shows_cards_counts_top_pages_and_top_projects_from_real_data(): void
    {
        $this->seed(ProjectSeeder::class);
        $this->seed(PortfolioContentSeeder::class);
        $project = Project::where('slug', 'fintrack')->firstOrFail();
        $this->recordView('/projetos/fintrack', 'now', ['project_id' => $project->id]);
        $this->recordView('/', 'now');

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
            ->assertSee('Projetos')->assertSee('Experiências')->assertSee('Tecnologias')->assertSee('Visitas')
            ->assertSee('Páginas mais acessadas')->assertSee('Projetos mais acessados')
            ->assertSee('/projetos/fintrack')->assertSee('FinTrack')
            ->assertSee('Acessos hoje')->assertSee('Últimos 7 dias')->assertSee('Últimos 30 dias');
    }

    public function test_dashboard_shows_empty_states_without_any_data(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
            ->assertSee('Ainda não há acessos neste período')
            ->assertSee('Nenhum projeto foi acessado no período.')
            ->assertSee('Nenhuma alteração registrada ainda');
    }
}
