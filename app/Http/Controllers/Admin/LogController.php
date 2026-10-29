<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $action = AuditAction::tryFrom((string) $request->query('action'));
        $resource = trim((string) $request->query('resource'));
        $search = trim((string) $request->query('q'));

        $logs = AuditLog::query()
            ->when($action, fn (Builder $q) => $q->where('action', $action->value))
            ->when($resource !== '', fn (Builder $q) => $q->where('resource_type', $resource))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('user_name', 'like', "%{$search}%")
                ->orWhere('resource_label', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'filters' => ['action' => $action?->value, 'resource' => $resource, 'search' => $search],
            'resources' => AuditLog::query()->distinct()->orderBy('resource_type')->pluck('resource_type'),
        ]);
    }
}
