<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Technology;
use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Analytics $analytics): View
    {
        $range = Analytics::normalizeRange($request->query('range'));
        $granularity = Analytics::normalizeGranularity($request->query('granularity'));

        return view('admin.dashboard', [
            'counts' => [
                'projects' => ['total' => Project::count(), 'published' => Project::where('is_active', true)->count()],
                'experiences' => ['total' => Experience::count(), 'published' => Experience::where('is_published', true)->count()],
                'technologies' => ['total' => Technology::count(), 'published' => Technology::where('is_published', true)->count()],
                'courses' => ['total' => Course::count(), 'published' => Course::where('is_published', true)->count()],
                'education' => ['total' => Education::count(), 'published' => Education::where('is_published', true)->count()],
            ],
            'summary' => $analytics->summary(),
            'range' => $range,
            'granularity' => $granularity,
            'series' => $analytics->series($range, $granularity),
            'topPages' => $analytics->topPages($range, 5),
            'topProjects' => $analytics->topProjects($range, 5),
            'devices' => $analytics->breakdown('device', $range, 4),
            'browsers' => $analytics->breakdown('browser', $range, 4),
            'referers' => $analytics->breakdown('referer_host', $range, 4),
            'recentLogs' => AuditLog::latest('id')->limit(6)->get(),
        ]);
    }
}
