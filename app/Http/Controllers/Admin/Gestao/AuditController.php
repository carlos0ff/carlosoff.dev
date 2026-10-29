<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __invoke(Request $request): View
    {
        $action = AuditAction::tryFrom((string) $request->query('action'));
        $search = trim((string) $request->query('q'));

        return view('admin.gestao.audit.index', [
            'logs' => AuditLog::query()
                ->where(fn (Builder $q) => $q->whereNotNull('client_id')->orWhereNotNull('client_project_id')
                    ->orWhereIn('resource_type', ['Credencial API', 'Modelo de documento', 'Assinatura', 'Pagamento', 'Acesso ao portal']))
                ->when($action, fn (Builder $q) => $q->where('action', $action->value))
                ->when($request->integer('client'), fn (Builder $q, $id) => $q->where('client_id', $id))
                ->when($request->integer('project'), fn (Builder $q, $id) => $q->where('client_project_id', $id))
                ->when($request->query('actor'), fn (Builder $q, $actor) => $q->where('actor_type', $actor))
                ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('resource_label', 'like', "%{$search}%")->orWhere('details', 'like', "%{$search}%")->orWhere('user_name', 'like', "%{$search}%")))
                ->latest('id')->paginate(30)->withQueryString(),
            'filters' => $request->only(['action', 'client', 'project', 'actor', 'q']),
            'clients' => Client::orderBy('legal_name')->pluck('legal_name', 'id'),
            'projects' => ClientProject::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
