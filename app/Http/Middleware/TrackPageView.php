<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\Project;
use App\Services\PortfolioContent;
use App\Support\UserAgent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra o acesso a páginas públicas para as estatísticas do painel.
 *
 * Privacidade: o IP nunca é salvo; guarda-se apenas um hash que muda todo dia (IP + navegador + data
 * + chave da aplicação), o que permite contar visitantes do dia sem rastrear a mesma pessoa entre dias.
 * O registro acontece em `terminate()` (depois da resposta) e uma falha aqui nunca quebra a página.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        try {
            $userAgent = (string) $request->userAgent();
            $agent = UserAgent::parse($userAgent);
            $project = $request->route('project');

            PageView::create([
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 191, ''),
                'route_name' => $request->route()?->getName(),
                'project_id' => $project instanceof Project ? $project->id : null,
                'referer_host' => $this->externalRefererHost($request),
                'device' => $agent['device'],
                'browser' => $agent['browser'],
                'os' => $agent['os'],
                'ip_hash' => hash('sha256', $request->ip().'|'.$userAgent.'|'.now()->toDateString().'|'.config('app.key')),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && ! $request->ajax()
            && ! $request->user()
            && ! UserAgent::isBot((string) $request->userAgent())
            && app(PortfolioContent::class)->setting('track_page_views');
    }

    /** Só o domínio de origem (nunca a URL completa) e apenas quando vem de outro site. */
    private function externalRefererHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host && $host !== $request->getHost() ? Str::limit($host, 120, '') : null;
    }
}
