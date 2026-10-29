<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ValidationOutcome;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Models\Document;
use App\Models\ProjectValidation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends PortalController
{
    public function __invoke(Request $request): View
    {
        $clientId = $this->clientId($request);
        $projectIds = ClientProject::where('client_id', $clientId)->pluck('id');

        return view('portal.dashboard', [
            'client' => $request->user()->client,
            'projects' => ClientProject::where('client_id', $clientId)->orderBy('name')->get(),
            'openCharges' => Charge::where('client_id', $clientId)->open()->orderBy('due_date')->get(),
            'pendingDocuments' => Document::where('client_id', $clientId)->where('visible_to_client', true)->whereIn('status', ['awaiting_signature', 'partially_signed'])->with('signers')->get(),
            'pendingValidations' => ProjectValidation::whereIn('client_project_id', $projectIds)->where('outcome', ValidationOutcome::Pending->value)->with('project')->get(),
            'notifications' => $request->user()->notifications()->latest()->limit(5)->get(),
        ]);
    }
}
