<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AboutController as AdminAboutController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\ExperienceController as AdminExperienceController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\Github\ContributionsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Páginas públicas: cada acesso é contabilizado nas estatísticas do painel (middleware `track`).
Route::middleware('track')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/sobre', AboutController::class)->name('about');
    Route::get('/projetos', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projetos/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/experiencia', ExperienceController::class)->name('experience');
    Route::get('/contato', ContactController::class)->name('contact');
});

Route::get('/contributions', [ContributionsController::class, 'graph'])->name('github.contributions');

// Link antigo (versão anterior do site vivia em /v2) — evita 404 para quem tinha salvo.
Route::redirect('/v2', '/');

// Login do painel: rota dedicada em /arena/entrar (nomes admin.login / admin.login.attempt).
Route::middleware('guest')->name('admin.')->group(function () {
    Route::get('arena/entrar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('arena/entrar', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
});

// Compatibilidade com a URL antiga.
Route::redirect('admin/login', '/arena/entrar');

Route::prefix('admin')->name('admin.')->group(function () {
    // Rota antiga do CRUD de projetos (em inglês).
    Route::redirect('projects', '/admin/projetos');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        // Projetos
        Route::get('projetos/{project}/visualizar', [AdminProjectController::class, 'preview'])->name('projects.preview');
        Route::patch('projetos/{project}/publicar', [AdminProjectController::class, 'toggle'])->name('projects.toggle');
        Route::patch('projetos/{project}/destaque', [AdminProjectController::class, 'feature'])->name('projects.feature');
        Route::patch('projetos/{project}/mover', [AdminProjectController::class, 'move'])->name('projects.move');
        Route::patch('projetos/{id}/restaurar', [AdminProjectController::class, 'restore'])->name('projects.restore');
        Route::resource('projetos', AdminProjectController::class)->parameters(['projetos' => 'project'])->except(['show'])->names('projects');

        // Conteúdo genérico (publicar, ordenar, restaurar + CRUD)
        foreach ([
            'experiencia' => [AdminExperienceController::class, 'experiences'],
            'formacao' => [EducationController::class, 'education'],
            'stack' => [TechnologyController::class, 'technologies'],
            'cursos-certificacoes' => [CourseController::class, 'courses'],
        ] as $uri => [$controller, $name]) {
            Route::patch("{$uri}/{id}/publicar", [$controller, 'toggle'])->name("{$name}.toggle");
            Route::patch("{$uri}/{id}/mover", [$controller, 'move'])->name("{$name}.move");
            Route::patch("{$uri}/{id}/restaurar", [$controller, 'restore'])->name("{$name}.restore");
            Route::resource($uri, $controller)->parameters([$uri => 'id'])->except(['show'])->names($name);
        }

        // Sobre
        Route::get('sobre', [AdminAboutController::class, 'edit'])->name('about.edit');
        Route::put('sobre', [AdminAboutController::class, 'update'])->name('about.update');

        // Analytics
        Route::get('analytics', [AnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('analytics/paginas', [AnalyticsController::class, 'pages'])->name('analytics.pages');
        Route::get('analytics/projetos', [AnalyticsController::class, 'projects'])->name('analytics.projects');

        // Sistema
        Route::get('logs', LogController::class)->name('logs.index');
        Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('usuarios', [UserController::class, 'store'])->name('users.store');
        Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('configuracoes', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('configuracoes', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('configuracoes/limpar-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    });
});
