<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AuditAction;
use App\Enums\StaffRole;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Services\Billing\ChargeService;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * O cliente consulta cobranças, envia comprovante (para conferência) e pode contestar.
 * Ele nunca altera status financeiro: o comprovante não dá baixa, quem dá é a equipe ou o provedor.
 */
class ChargeController extends PortalController
{
    public function index(Request $request): View
    {
        return view('portal.charges.index', [
            'charges' => Charge::where('client_id', $this->clientId($request))->with(['project', 'payments'])
                ->whereNot('status', 'cancelled')->orderByDesc('due_date')->paginate(20),
        ]);
    }

    public function uploadProof(Request $request, Charge $charge, Notifier $notifier): RedirectResponse
    {
        $this->ensureOwned($request, $charge);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['file' => 'comprovante']);

        $file = $request->file('file');
        $charge->attachments()->create([
            'label' => 'Comprovante enviado pelo cliente'.(filled($data['notes'] ?? null) ? ': '.$data['notes'] : ''),
            'path' => $file->store("attachments/charge/{$charge->id}", 'local'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'visible_to_client' => true,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::forProject($charge->project, AuditAction::Create, 'Comprovante', $charge, $charge->description, 'Enviado pelo cliente para conferência', visibleToClient: true);
        $notifier->staff("Comprovante recebido: {$charge->description}", 'Confira e registre a baixa se o pagamento for confirmado.', route('admin.gestao.charges.show', $charge), 'financeiro', [StaffRole::Admin, StaffRole::Finance]);

        return back()->with('status', 'Comprovante enviado. A cobrança será baixada após a conferência.');
    }

    public function dispute(Request $request, Charge $charge, ChargeService $charges): RedirectResponse
    {
        $this->ensureOwned($request, $charge);
        abort_unless($charge->isOpen(), 422);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']], [], ['reason' => 'motivo'])['reason'];
        $charges->dispute($charge, "Cliente ({$request->user()->email}): {$reason}");

        return back()->with('status', 'Contestação registrada. Nenhuma restrição será aplicada enquanto ela estiver em análise.');
    }
}
