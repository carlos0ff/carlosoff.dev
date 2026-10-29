<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function overview(Request $request, Analytics $analytics): View
    {
        $range = Analytics::normalizeRange($request->query('range'));
        $granularity = Analytics::normalizeGranularity($request->query('granularity'));

        return view('admin.analytics.overview', [
            'summary' => $analytics->summary(),
            'range' => $range,
            'granularity' => $granularity,
            'series' => $analytics->series($range, $granularity),
            'devices' => $analytics->breakdown('device', $range),
            'browsers' => $analytics->breakdown('browser', $range),
            'systems' => $analytics->breakdown('os', $range),
            'referers' => $analytics->breakdown('referer_host', $range),
        ]);
    }

    public function pages(Request $request, Analytics $analytics): View
    {
        $range = Analytics::normalizeRange($request->query('range'));

        return view('admin.analytics.pages', [
            'range' => $range,
            'total' => $analytics->totalInRange($range),
            'pages' => $analytics->topPages($range, 50),
        ]);
    }

    public function projects(Request $request, Analytics $analytics): View
    {
        $range = Analytics::normalizeRange($request->query('range'));

        return view('admin.analytics.projects', [
            'range' => $range,
            'projects' => $analytics->topProjects($range, 50),
        ]);
    }
}
