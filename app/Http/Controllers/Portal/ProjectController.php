<?php

namespace App\Http\Controllers\Portal;

use App\Models\AuditLog;
use App\Models\ClientProject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends PortalController
{
    public function show(Request $request, ClientProject $project): View
    {
        $this->ensureProject($request, $project);
        $project->load(['domains', 'hostingServices', 'validations' => fn ($q) => $q->latest(), 'activeContract']);

        return view('portal.projects.show', [
            'project' => $project,
            'documents' => $project->documents()->where('visible_to_client', true)->whereNotIn('status', ['draft', 'under_review'])->latest()->get(),
            'charges' => $project->charges()->latest('due_date')->limit(12)->get(),
            'scopeRequests' => $project->scopeRequests()->latest()->get(),
            'tickets' => $project->tickets()->latest()->limit(10)->get(),
            // Somente eventos marcados como visíveis ao cliente (nada de anotações internas).
            'history' => AuditLog::where('client_project_id', $project->id)->where('visible_to_client', true)->latest('id')->limit(25)->get(),
        ]);
    }
}
