<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\GitHub\GitHubContributionService;
use App\Services\PortfolioContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, GitHubContributionService $contributions, PortfolioContent $content): View
    {
        $year = $request->query('year', 'current');
        $year = $year !== 'current' ? (int) $year : $year;

        $graph = $contributions->getContributionGraph(config('services.github.username'), $year);

        // Projetos em destaque; se nenhum estiver marcado como destaque, usa os publicados mais recentes.
        $featuredProjects = Project::published()->where('featured', true)->ordered()->limit(4)->get();
        if ($featuredProjects->isEmpty()) {
            $featuredProjects = Project::published()->ordered()->limit(4)->get();
        }

        return view('pages.home', [
            'graph' => $graph,
            'experiences' => $content->experiences(),
            'featuredProjects' => $featuredProjects,
            'about' => $content->about(),
            'stack' => $content->stack(),
            'stats' => [
                ['label' => 'projetos', 'value' => (string) Project::published()->count()],
                ['label' => 'tecnologias', 'value' => (string) count($content->stack())],
                ['label' => 'experiências', 'value' => (string) count($content->experiences())],
                ['label' => 'formações', 'value' => (string) count($content->education())],
            ],
        ]);
    }
}
