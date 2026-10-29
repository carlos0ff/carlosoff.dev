<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestão e portal exibem datas relativas e nomes de meses em português, sem alterar o locale global do site.
 */
class UseBrazilianDates
{
    public function handle(Request $request, Closure $next): Response
    {
        Carbon::setLocale('pt_BR');

        return $next($request);
    }
}
