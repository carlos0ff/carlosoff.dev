<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\CredentialKind;
use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\ClientProject;
use App\Models\SignatureEvent;
use App\Services\Api\CredentialService;
use App\Services\Signature\SignatureManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Configurações de API e integrações. Segredos aparecem uma única vez (flash) e nunca em listagens.
 */
class IntegrationController extends Controller
{
    public function __construct(protected CredentialService $credentials) {}

    public function index(SignatureManager $signatures): View
    {
        return view('admin.gestao.integrations.index', [
            'credentials' => ApiCredential::with(['project', 'user'])->latest()->get(),
            'signature' => [
                'driver' => config('gestao.signature.driver'),
                'configured' => $signatures->isConfigured(),
                'base_url' => config('gestao.signature.clicksign.base_url'),
                'webhook_secret' => filled(config('gestao.signature.clicksign.webhook_secret')),
                'webhook_url' => route('webhooks.signature', 'clicksign'),
                'last_event' => SignatureEvent::latest('id')->first(),
            ],
            'payments' => ['driver' => config('gestao.payments.driver')],
            'apiHttps' => (bool) config('gestao.api.require_https'),
        ]);
    }

    public function storeInstallation(Request $request, ClientProject $project): RedirectResponse
    {
        $name = $request->validate(['name' => ['required', 'string', 'max:120']])['name'];
        $issued = $this->credentials->issueForInstallation($project, $name, $request->user());

        return back()->with('status', 'Credencial emitida. Copie o segredo agora: ele não será exibido novamente.')
            ->with('issued_secret', ['key_id' => $issued['credential']->key_id, 'secret' => $issued['secret'], 'project' => $project->uuid]);
    }

    public function storeAutomation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys(ApiCredential::ABILITIES))],
        ]);

        try {
            $issued = $this->credentials->issueForAutomation($request->user(), $data['name'], $data['abilities'], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['credential' => $exception->getMessage()]);
        }

        return back()->with('status', 'Credencial de automação emitida. Copie o segredo agora.')
            ->with('issued_secret', ['key_id' => $issued['credential']->key_id, 'secret' => $issued['secret'], 'project' => null]);
    }

    public function rotate(Request $request, ApiCredential $credential): RedirectResponse
    {
        $this->authorizeCredential($request, $credential);

        try {
            $issued = $this->credentials->rotate($credential, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['credential' => $exception->getMessage()]);
        }

        return back()->with('status', 'Credencial rotacionada. A anterior expira em '.config('gestao.api.rotation_grace_hours').'h.')
            ->with('issued_secret', ['key_id' => $issued['credential']->key_id, 'secret' => $issued['secret'], 'project' => $credential->project?->uuid]);
    }

    public function revoke(Request $request, ApiCredential $credential): RedirectResponse
    {
        $this->authorizeCredential($request, $credential);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];
        $this->credentials->revoke($credential, $reason);

        return back()->with('status', 'Credencial revogada imediatamente.');
    }

    protected function authorizeCredential(Request $request, ApiCredential $credential): void
    {
        // Credenciais de automação só são geridas por administradores.
        abort_if($credential->kind === CredentialKind::Automation && ! $request->user()->can('gestao.admin'), 403);
    }
}
