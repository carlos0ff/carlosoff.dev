<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContractTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modelos de documento. Qualquer edição invalida a revisão jurídica anterior (nova versão precisa ser revisada).
 */
class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.gestao.templates.index', ['templates' => ContractTemplate::with('reviewer')->orderBy('document_type')->get()]);
    }

    public function edit(ContractTemplate $template): View
    {
        return view('admin.gestao.templates.edit', ['template' => $template]);
    }

    public function update(Request $request, ContractTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:200000'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $template->fill($data);
        if ($template->isDirty('body')) {
            $template->version++;
            $template->legal_reviewed_at = null;
            $template->legal_reviewed_by = null;
            $template->legal_review_notes = null;
        }
        $template->save();

        AuditLog::record(AuditAction::Update, 'Modelo de documento', $template->id, $template->name, "v{$template->version}");

        return redirect()->route('admin.gestao.templates.edit', $template)->with('status', 'Modelo salvo. Se o texto mudou, ele precisa de nova revisão jurídica.');
    }

    public function approve(Request $request, ContractTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'legal_review_notes' => ['required', 'string', 'max:255'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Confirme que o modelo foi revisado por profissional jurídico habilitado.'], ['legal_review_notes' => 'identificação da revisão']);

        $template->forceFill(['legal_reviewed_at' => now(), 'legal_reviewed_by' => $request->user()->id, 'legal_review_notes' => $data['legal_review_notes']])->save();
        AuditLog::record(AuditAction::Approve, 'Modelo de documento', $template->id, $template->name, "Revisão jurídica v{$template->version}: {$data['legal_review_notes']}");

        return back()->with('status', 'Revisão jurídica registrada.');
    }
}
