<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PortfolioContent;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(PortfolioContent $content): View
    {
        return view('pages.projects', [
            'projects' => Project::published()
                ->ordered()
                ->paginate($content->projectsPerPage()),
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_active && $project->hasDetailPage(), 404);

        return view('pages.project', [
            'project' => $project,
            'related' => $project->relatedProjects(),
        ]);
    }
}
