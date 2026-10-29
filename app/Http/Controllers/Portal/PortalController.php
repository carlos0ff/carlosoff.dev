<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use Illuminate\Http\Request;

/**
 * Base do portal: toda consulta é restrita à empresa do usuário logado.
 * Recurso de outro cliente responde 404 (não revela que existe).
 */
abstract class PortalController extends Controller
{
    protected function clientId(Request $request): int
    {
        return (int) $request->user()->client_id;
    }

    /**
     * @param  object{client_id: int}|null  $model
     */
    protected function ensureOwned(Request $request, ?object $model): void
    {
        abort_unless($model !== null && (int) $model->client_id === $this->clientId($request), 404);
    }

    protected function ensureProject(Request $request, ClientProject $project): void
    {
        $this->ensureOwned($request, $project);
    }
}
