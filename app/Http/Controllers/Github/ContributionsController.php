<?php

namespace App\Http\Controllers\Github;

use App\Http\Controllers\Controller;
use App\Services\GitHub\GitHubContributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributionsController extends Controller
{
    public function graph(Request $request, GitHubContributionService $contributions): JsonResponse
    {
        $year = $request->query('year', 'current');
        $year = $year !== 'current' ? (int) $year : $year;

        return response()->json(
            $contributions->getContributionGraph(config('services.github.username'), $year)
        );
    }
}
