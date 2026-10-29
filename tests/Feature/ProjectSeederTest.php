<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_five_portfolio_projects_without_invented_urls(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->assertSame(
            ['Currencee', 'FinTrack', 'WorkFacilit', 'VigiaSUS', 'PicPay Wallet'],
            Project::ordered()->pluck('title')->all(),
        );

        $finTrack = Project::where('title', 'FinTrack')->firstOrFail();
        $this->assertSame('fintrack', $finTrack->slug);
        $this->assertSame('image/projetos/organizze/Dashboard.png', $finTrack->image);
        $this->assertSame(['Laravel', 'PHP', 'Vue 3', 'Inertia.js', 'Tailwind CSS', 'PostgreSQL', 'Docker'], $finTrack->technologies);

        $this->assertSame(0, Project::whereNotNull('github_url')->orWhereNotNull('demo_url')->count());
        $this->assertSame(['Java', 'Spring Boot', 'PostgreSQL', 'Neo4j', 'Docker', 'n8n'], Project::where('title', 'VigiaSUS')->firstOrFail()->technologies);
    }

    public function test_currencee_is_seeded_with_details_and_only_the_facts_provided(): void
    {
        $this->seed(ProjectSeeder::class);

        $currencee = Project::where('slug', 'currencee')->firstOrFail();

        $this->assertTrue($currencee->hasDetailPage());
        $this->assertSame(['React', 'Vite', 'Node.js', 'JavaScript', 'CSS', 'Express', 'API de moedas'], $currencee->technologies);
        $this->assertNull($currencee->github_url);
        $this->assertNull($currencee->demo_url);
        $this->assertNull($currencee->image);
        $this->assertSame([], $currencee->details['screenshots']);
    }

    public function test_every_project_has_a_detail_page_with_its_own_slug(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->assertSame(
            ['currencee', 'fintrack', 'workfacilit', 'vigiasus', 'picpay-wallet'],
            Project::ordered()->pluck('slug')->all(),
        );
        $this->assertSame(5, Project::all()->filter->hasDetailPage()->count());
    }

    public function test_legacy_realizze_project_is_renamed_to_fintrack_and_gets_its_details(): void
    {
        Project::factory()->create(['title' => 'Realizze', 'slug' => 'realizze', 'featured' => true, 'details' => null]);

        $this->seed(ProjectSeeder::class);

        $this->assertSame(0, Project::where('title', 'Realizze')->count());
        $finTrack = Project::where('title', 'FinTrack')->firstOrFail();
        $this->assertSame('fintrack', $finTrack->slug);
        $this->assertFalse($finTrack->featured);
        $this->assertTrue($finTrack->hasDetailPage());
        $this->assertSame(5, Project::count());
    }

    public function test_it_is_idempotent_and_keeps_edits_made_in_the_admin(): void
    {
        $this->seed(ProjectSeeder::class);
        Project::where('title', 'WorkFacilit')->update(['github_url' => 'https://github.com/exemplo/workfacilit', 'is_active' => false]);

        $this->seed(ProjectSeeder::class);

        $this->assertSame(5, Project::count());
        $workFacilit = Project::where('title', 'WorkFacilit')->firstOrFail();
        $this->assertSame('https://github.com/exemplo/workfacilit', $workFacilit->github_url);
        $this->assertFalse($workFacilit->is_active);
    }
}
