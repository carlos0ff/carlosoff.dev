<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SiteContent;
use App\Services\PortfolioContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(PortfolioContent $content): View
    {
        return view('admin.settings.edit', ['settings' => $content->settings()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'projects_per_page' => ['required', 'integer', 'min:1', 'max:24'],
            'featured_projects_limit' => ['required', 'integer', 'min:0', 'max:20'],
            'track_page_views' => ['boolean'],
        ], [], ['projects_per_page' => 'projetos por página', 'featured_projects_limit' => 'limite de destaques']);

        SiteContent::write(SiteContent::SETTINGS, [
            'projects_per_page' => (int) $validated['projects_per_page'],
            'featured_projects_limit' => (int) $validated['featured_projects_limit'],
            'track_page_views' => $request->boolean('track_page_views'),
        ]);

        AuditLog::record(AuditAction::Update, 'Configurações', null, 'Configurações do site');

        return redirect()->route('admin.settings.edit')->with('status', 'Configurações salvas com sucesso.');
    }

    public function clearCache(): RedirectResponse
    {
        PortfolioContent::flush();

        return redirect()->route('admin.settings.edit')->with('status', 'Cache do conteúdo público limpo.');
    }
}
