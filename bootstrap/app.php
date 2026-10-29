<?php

use App\Http\Middleware\AuthenticateApiSignature;
use App\Http\Middleware\EnsurePortalUser;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\TrackPageView;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/gestao.php'],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal', 'portal/*') ? route('portal.login') : route('admin.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isPortalUser() ? route('portal.dashboard') : route('admin.dashboard'));

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'track' => TrackPageView::class,
            'portal' => EnsurePortalUser::class,
            'api.signature' => AuthenticateApiSignature::class,
        ]);

        // Autentica a assinatura antes de resolver o projeto: requisição anônima não descobre se um UUID existe.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AuthenticateApiSignature::class);

        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Painel: um item que não existe mais (excluído em outra aba, lista desatualizada, clique duplo)
        // volta para a lista com um aviso, em vez de mostrar a página crua de 404.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('admin/*') || ! $request->user()?->is_admin || ! $exception->getPrevious() instanceof ModelNotFoundException) {
                return null;
            }

            return redirect('/admin/'.$request->segment(2))
                ->withErrors(['not_found' => 'Este item não existe mais (talvez já tenha sido excluído). A lista foi atualizada.']);
        });
    })->create();
