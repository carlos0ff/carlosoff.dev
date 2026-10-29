<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal do cliente: apenas usuários vinculados a uma empresa cliente (a equipe usa /admin).
 */
class EnsurePortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPortalUser(), 403, 'Acesso restrito ao portal do cliente.');

        return $next($request);
    }
}
