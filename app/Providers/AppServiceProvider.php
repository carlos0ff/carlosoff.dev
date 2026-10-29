<?php

namespace App\Providers;

use App\Enums\StaffRole;
use App\Models\ApiCredential;
use App\Models\User;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Signature\SignatureManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SignatureManager::class);
        $this->app->singleton(PaymentGatewayManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // URLs em português no painel: /admin/projetos/criar e /admin/projetos/{id}/editar.
        Route::resourceVerbs(['create' => 'criar', 'edit' => 'editar']);

        // Gestão: autorização por função (além do middleware `admin`).
        Gate::define('gestao.view', fn (User $user) => $user->isStaff());
        Gate::define('gestao.finance', fn (User $user) => $user->hasRole(StaffRole::Admin, StaffRole::Finance));
        Gate::define('gestao.operations', fn (User $user) => $user->hasRole(StaffRole::Admin, StaffRole::Technical));
        Gate::define('gestao.admin', fn (User $user) => $user->hasRole(StaffRole::Admin));

        RateLimiter::for('gestao-api', function (Request $request) {
            /** @var ApiCredential|null $credential */
            $credential = $request->attributes->get('api_credential');

            return Limit::perMinute((int) config('gestao.api.rate_limit_per_minute'))->by($credential?->key_id ?? $request->ip());
        });

        RateLimiter::for('portal-login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
