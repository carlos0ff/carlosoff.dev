<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\Domain;
use App\Models\MonitoringIncident;
use App\Services\Monitoring\HealthProbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Monitoramento técnico — separado do financeiro (um projeto inadimplente não aparece como "fora do ar").
 */
class MonitoringController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        return view('admin.gestao.monitoring.index', [
            'projects' => ClientProject::with(['client', 'latestHealthCheck'])
                ->where('monitoring_enabled', true)
                ->orderBy('name')->get()
                ->map(fn (ClientProject $project) => [
                    'project' => $project,
                    'probe' => $project->healthChecks()->where('source', 'probe')->latest('checked_at')->first(),
                    'agent' => $project->healthChecks()->where('source', 'agent')->latest('checked_at')->first(),
                ]),
            'incidents' => MonitoringIncident::with('project')->latest('opened_at')->limit(20)->get(),
            'domains' => Domain::with('project')->where(fn ($q) => $q->whereNotNull('expires_at')->orWhereNotNull('ssl_expires_at'))
                ->orderByRaw('coalesce(ssl_expires_at, expires_at)')->limit(30)->get(),
            'today' => $today,
        ]);
    }

    public function probe(ClientProject $project, HealthProbe $probe): RedirectResponse
    {
        $check = $probe->probe($project);

        return back()->with('status', $check ? ($check->is_up ? "Online — HTTP {$check->http_status} em {$check->response_time_ms} ms." : "Falha: {$check->error}") : 'Projeto sem URL de produção.');
    }
}
