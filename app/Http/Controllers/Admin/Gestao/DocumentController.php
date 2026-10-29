<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\SignerRole;
use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Document;
use App\Models\DocumentSigner;
use App\Models\ProjectValidation;
use App\Models\ScopeRequest;
use App\Services\Contracts\DocumentService;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureProviderException;
use App\Support\BrazilianDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $documents) {}

    public function index(Request $request): View
    {
        $status = DocumentStatus::tryFrom((string) $request->query('status'));
        $type = DocumentType::tryFrom((string) $request->query('type'));

        return view('admin.gestao.documents.index', [
            'documents' => Document::with(['project', 'client', 'signers'])
                ->when($status, fn (Builder $q) => $q->where('status', $status->value))
                ->when($type, fn (Builder $q) => $q->where('type', $type->value))
                ->latest()->paginate(20)->withQueryString(),
            'current' => $status?->value ?? 'all',
            'type' => $type?->value,
            'counts' => ['all' => Document::count()] + Document::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'signatureConfigured' => app(SignatureManager::class)->isConfigured(),
        ]);
    }

    public function store(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'contract_id' => ['nullable', Rule::exists('contracts', 'id')->where('client_project_id', $project->id)],
            'scope_request_id' => ['nullable', Rule::exists('scope_requests', 'id')->where('client_project_id', $project->id)],
            'project_validation_id' => ['nullable', Rule::exists('project_validations', 'id')->where('client_project_id', $project->id)],
            'template_id' => ['nullable', 'exists:contract_templates,id'],
        ]);
        $type = DocumentType::from($data['type']);

        if ($type === DocumentType::ScopeChange && empty($data['scope_request_id'])) {
            return back()->withErrors(['scope_request_id' => 'Selecione a solicitação fora do escopo.']);
        }
        if ($type === DocumentType::AcceptanceTerm && empty($data['project_validation_id'])) {
            return back()->withErrors(['project_validation_id' => 'Selecione a validação de versão.']);
        }

        try {
            $document = $this->documents->generate(
                $project, $type, $request->user(),
                isset($data['template_id']) ? ContractTemplate::find($data['template_id']) : null,
                isset($data['contract_id']) ? Contract::find($data['contract_id']) : null,
                isset($data['scope_request_id']) ? ScopeRequest::find($data['scope_request_id']) : null,
                isset($data['project_validation_id']) ? ProjectValidation::find($data['project_validation_id']) : null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['type' => $exception->getMessage()]);
        }

        return redirect()->route('admin.gestao.documents.show', $document)->with('status', 'Documento gerado como rascunho. Revise o texto e os signatários.');
    }

    public function show(Document $document): View
    {
        $document->load(['project.client', 'template', 'signers', 'events', 'contract', 'scopeRequest', 'validation']);

        return view('admin.gestao.documents.show', [
            'document' => $document,
            'signatureConfigured' => app(SignatureManager::class)->isConfigured(),
            'missing' => substr_count($document->body_html, 'class="missing"'),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate(['body_html' => ['required', 'string', 'max:500000']]);

        return $this->attempt(fn () => $this->documents->updateBody($document, $this->sanitize($data['body_html'])), 'Texto atualizado.');
    }

    public function review(Document $document): RedirectResponse
    {
        return $this->attempt(fn () => $this->documents->submitForReview($document), 'Documento enviado para revisão.');
    }

    public function lock(Request $request, Document $document): RedirectResponse
    {
        return $this->attempt(fn () => $this->documents->lock($document, $request->user()), 'Versão congelada: PDF gerado e hash registrado.');
    }

    public function send(Document $document): RedirectResponse
    {
        return $this->attempt(fn () => $this->documents->send($document), 'Envio ao provedor de assinatura agendado.');
    }

    public function sync(Document $document): RedirectResponse
    {
        if (! $document->provider_envelope_id) {
            return back()->withErrors(['document' => 'Documento ainda não foi enviado ao provedor.']);
        }

        return $this->attempt(fn () => $this->documents->sync($document), 'Situação sincronizada com o provedor.');
    }

    public function cancel(Request $request, Document $document): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        return $this->attempt(fn () => $this->documents->cancel($document, $reason), 'Documento cancelado.');
    }

    public function storeSigner(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(SignerRole::class)],
            'auth_method' => ['required', 'in:email,sms,whatsapp,icp_brasil'],
            'sign_order' => ['required', 'integer', 'between:1,10'],
        ], [], ['name' => 'nome']);

        if (filled($data['document_number'] ?? null) && ! BrazilianDocument::isValidCpf($data['document_number'])) {
            return back()->withErrors(['document_number' => 'CPF do signatário inválido.'])->withInput();
        }
        $data['document_number'] = filled($data['document_number'] ?? null) ? BrazilianDocument::digits($data['document_number']) : null;

        return $this->attempt(fn () => $this->documents->addSigner($document, $data), 'Signatário adicionado.');
    }

    public function destroySigner(Document $document, DocumentSigner $signer): RedirectResponse
    {
        abort_unless($signer->document_id === $document->id, 404);

        return $this->attempt(fn () => $this->documents->removeSigner($signer), 'Signatário removido.');
    }

    public function download(Document $document, string $variant): Response
    {
        if ($variant === 'previa') {
            return response($this->documents->renderPdf($document, draft: ! $document->isLocked()), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="previa-'.$document->uuid.'.pdf"',
            ]);
        }

        $path = $variant === 'original' ? $document->file_path : $document->signed_file_path;
        abort_unless($path && Storage::disk(DocumentService::DISK)->exists($path), 404);

        return Storage::disk(DocumentService::DISK)->download($path, "{$variant}-{$document->uuid}.pdf");
    }

    protected function attempt(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException|SignatureProviderException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return back()->with('status', $message);
    }

    /**
     * Texto editado pela equipe: remove scripts, handlers e iframes (o PDF só precisa de HTML simples).
     */
    protected function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        return preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html) ?? '';
    }
}
