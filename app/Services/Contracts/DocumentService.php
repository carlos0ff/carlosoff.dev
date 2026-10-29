<?php

namespace App\Services\Contracts;

use App\Enums\AuditAction;
use App\Enums\ContractStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\ScopeRequestStatus;
use App\Enums\SignerStatus;
use App\Jobs\DownloadSignedDocument;
use App\Jobs\SendDocumentForSignature;
use App\Models\AuditLog;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Document;
use App\Models\DocumentSigner;
use App\Models\ProjectValidation;
use App\Models\ScopeRequest;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureProviderException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Ciclo do documento: rascunho → revisão → versão imutável (PDF + SHA-256) → provedor → eventos → PDF assinado.
 */
class DocumentService
{
    public const DISK = 'local';

    public function __construct(
        protected TemplateRenderer $renderer,
        protected SignatureManager $signatures,
        protected ContractService $contracts,
        protected Notifier $notifier,
    ) {}

    public function generate(ClientProject $project, DocumentType $type, ?User $user, ?ContractTemplate $template = null, ?Contract $contract = null, ?ScopeRequest $scope = null, ?ProjectValidation $validation = null): Document
    {
        $template ??= ContractTemplate::where('document_type', $type->value)->where('is_active', true)->latest('id')->first();
        if (! $template) {
            throw new InvalidArgumentException("Não há modelo ativo para: {$type->label()}.");
        }

        if ($scope && $scope->client_project_id !== $project->id || $validation && $validation->client_project_id !== $project->id || $contract && $contract->client_project_id !== $project->id) {
            throw new InvalidArgumentException('Vínculos do documento pertencem a outro projeto.');
        }

        $contract ??= $type === DocumentType::DevelopmentContract || $type === DocumentType::CommercialTerms ? $project->contracts()->latest('id')->first() : null;
        $variables = $this->renderer->variables($project, $contract, $scope, $validation);

        $previous = Document::where('client_project_id', $project->id)->where('type', $type->value)
            ->where('scope_request_id', $scope?->id)->where('project_validation_id', $validation?->id)
            ->latest('version')->first();

        $document = Document::create([
            'client_id' => $project->client_id,
            'client_project_id' => $project->id,
            'contract_id' => $contract?->id,
            'scope_request_id' => $scope?->id,
            'project_validation_id' => $validation?->id,
            'contract_template_id' => $template->id,
            'template_version' => $template->version,
            'type' => $type,
            'title' => $type->label().' — '.$project->name.($scope ? " — #{$scope->id}" : '').($validation ? " — v{$validation->version}" : ''),
            'version' => ($previous?->version ?? 0) + 1,
            'supersedes_id' => $previous?->id,
            'body_html' => $this->renderer->render($template->body, $variables),
            'visible_to_client' => true,
            'created_by' => $user?->id,
        ]);
        $document->forceFill(['status' => DocumentStatus::Draft])->save();

        $this->addDefaultSigners($document, $project);

        AuditLog::forProject($project, AuditAction::Generate, 'Documento', $document, $document->title, "v{$document->version} (modelo {$template->name} v{$template->version})");

        return $document;
    }

    public function updateBody(Document $document, string $html): void
    {
        $this->assertEditable($document);
        $document->forceFill(['body_html' => $html])->save();
        AuditLog::forProject($document->project, AuditAction::Update, 'Documento', $document, $document->title, 'Conteúdo editado');
    }

    public function submitForReview(Document $document): void
    {
        $this->assertEditable($document);
        $document->forceFill(['status' => DocumentStatus::UnderReview])->save();
        AuditLog::forProject($document->project, AuditAction::Update, 'Documento', $document, $document->title, 'Enviado para revisão');
    }

    /**
     * Congela a versão para assinatura: gera o PDF, grava o SHA-256 e impede novas edições.
     */
    public function lock(Document $document, User $user): void
    {
        if ($document->isLocked()) {
            throw new InvalidArgumentException('Documento já está congelado.');
        }
        if ($document->status !== DocumentStatus::UnderReview) {
            throw new InvalidArgumentException('Envie o documento para revisão antes de congelar.');
        }
        if ($document->template && ! $document->template->isLegallyReviewed()) {
            throw new InvalidArgumentException('O modelo usado ainda não foi aprovado em revisão jurídica.');
        }
        if (str_contains($document->body_html, 'class="missing"')) {
            throw new InvalidArgumentException('Há campos não preenchidos no documento.');
        }
        if ($document->signers()->count() === 0) {
            throw new InvalidArgumentException('Adicione ao menos um signatário.');
        }

        $pdf = $this->renderPdf($document);
        $path = "documents/{$document->uuid}/v{$document->version}-original.pdf";
        Storage::disk(self::DISK)->put($path, $pdf);

        $document->forceFill([
            'file_path' => $path,
            'file_sha256' => hash('sha256', $pdf),
            'locked_at' => now(),
            'locked_by' => $user->id,
        ])->save();

        AuditLog::forProject($document->project, AuditAction::Lock, 'Documento', $document, $document->title, 'SHA-256 '.$document->file_sha256);
    }

    public function renderPdf(Document $document, bool $draft = false): string
    {
        return Pdf::loadView('pdf.document', [
            'document' => $document,
            'draft' => $draft || ($document->template && ! $document->template->isLegallyReviewed()),
            'company' => config('gestao.company'),
        ])->setPaper('a4')->output();
    }

    public function send(Document $document): void
    {
        if (! $document->isLocked()) {
            throw new InvalidArgumentException('Congele a versão do documento antes de enviar.');
        }
        if ($document->provider_envelope_id) {
            throw new InvalidArgumentException('Documento já enviado ao provedor.');
        }
        if (! $this->signatures->isConfigured()) {
            throw new InvalidArgumentException('Provedor de assinatura não configurado (CLICKSIGN_ACCESS_TOKEN).');
        }

        $document->forceFill(['provider' => config('gestao.signature.driver'), 'provider_error' => null])->save();
        SendDocumentForSignature::dispatch($document->id);
    }

    /**
     * Executado pela fila (com retries). Confere a integridade do PDF antes de enviar.
     */
    public function performSend(Document $document): void
    {
        if ($document->provider_envelope_id) {
            return;
        }

        $pdf = Storage::disk(self::DISK)->get($document->file_path);
        if ($pdf === null || ! hash_equals($document->file_sha256, hash('sha256', $pdf))) {
            $document->forceFill(['provider_error' => 'Integridade do PDF não confere com o hash registrado.'])->save();
            throw new SignatureProviderException('Integridade do PDF não confere.');
        }

        $result = $this->signatures->driver($document->provider)->send($document->load('signers'), $pdf);

        DB::transaction(function () use ($document, $result) {
            $document->forceFill([
                'provider_envelope_id' => $result['envelope_id'],
                'provider_document_id' => $result['document_id'],
                'status' => DocumentStatus::AwaitingSignature,
                'sent_at' => now(),
                'expires_at' => now()->addDays((int) config('gestao.signature.clicksign.deadline_days')),
                'provider_error' => null,
            ])->save();

            foreach ($document->signers as $signer) {
                if (isset($result['signers'][$signer->email])) {
                    $signer->forceFill(['provider_signer_id' => $result['signers'][$signer->email]['id'], 'sign_url' => $result['signers'][$signer->email]['url']])->save();
                }
            }
        });

        AuditLog::forProject($document->project, AuditAction::Send, 'Documento', $document, $document->title, "Enviado para assinatura ({$document->provider})", ['envelope' => $result['envelope_id']], visibleToClient: true, actorName: 'Sistema', actorType: 'system');

        if ($document->contract_id && $document->type === DocumentType::DevelopmentContract) {
            $document->contract->forceFill(['status' => ContractStatus::AwaitingSignature])->save();
            $this->contracts->syncProjectStatus($document->project);
        }

        $this->notifier->client($document->client, "Documento aguardando sua assinatura: {$document->title}", [
            'O link de assinatura foi enviado pelo provedor de assinatura eletrônica ao e-mail de cada signatário.',
            'Você também pode acompanhar o andamento pelo portal.',
        ], 'Abrir portal', route('portal.documents.show', $document), 'documentos');
    }

    /**
     * Aplica um evento normalizado do provedor. Idempotente: reprocessar o mesmo evento não muda nada.
     *
     * @param  array{event: string, signer_email: string|null, signer_id: string|null, occurred_at: string|null, signed_file_url: string|null, evidence: array<string, mixed>}  $event
     */
    public function applyProviderEvent(Document $document, array $event): void
    {
        if ($document->isFinal() && $document->status !== DocumentStatus::Signed) {
            return;
        }

        $occurredAt = $event['occurred_at'] ? Carbon::parse($event['occurred_at']) : now();

        switch ($event['event']) {
            case 'sign':
            case 'acceptance_term_completed':
                $signer = $this->findSigner($document, $event);
                if ($signer && $signer->status !== SignerStatus::Signed) {
                    $signer->forceFill(['status' => SignerStatus::Signed, 'signed_at' => $occurredAt, 'evidence' => $event['evidence']])->save();
                    AuditLog::forProject($document->project, AuditAction::Sign, 'Documento', $document, $document->title, "{$signer->name} assinou", ['evidence' => $event['evidence']], visibleToClient: true, actorName: $signer->name, actorType: 'signer');
                }
                // "Assinado" só quando o provedor fecha o envelope (arquivo final disponível).
                if (! $document->isFinal()) {
                    $document->forceFill(['status' => DocumentStatus::PartiallySigned])->save();
                }
                break;

            case 'refusal':
            case 'acceptance_term_refused':
                $signer = $this->findSigner($document, $event);
                $signer?->forceFill(['status' => SignerStatus::Refused, 'evidence' => $event['evidence']])->save();
                $document->forceFill(['status' => DocumentStatus::Refused, 'completed_at' => $occurredAt])->save();
                AuditLog::forProject($document->project, AuditAction::Reject, 'Documento', $document, $document->title, 'Recusado'.($signer ? " por {$signer->name}" : ''), visibleToClient: true, actorName: $signer?->name ?? 'Provedor', actorType: 'signer');
                $this->notifier->staff("Documento recusado: {$document->title}", 'O signatário recusou a assinatura.', route('admin.gestao.documents.show', $document), 'documentos');
                break;

            case 'deadline':
            case 'acceptance_term_expired':
                $document->forceFill(['status' => DocumentStatus::Expired, 'completed_at' => $occurredAt])->save();
                AuditLog::forProject($document->project, AuditAction::Update, 'Documento', $document, $document->title, 'Prazo de assinatura expirado', visibleToClient: true, actorName: 'Provedor', actorType: 'system');
                break;

            case 'cancel':
            case 'acceptance_term_canceled':
                $document->forceFill(['status' => DocumentStatus::Cancelled, 'completed_at' => $occurredAt])->save();
                break;

            case 'auto_close':
            case 'close':
            case 'document_closed':
                if ($document->status !== DocumentStatus::Signed) {
                    $document->forceFill(['status' => DocumentStatus::Signed, 'completed_at' => $occurredAt])->save();
                    $document->signers()->where('status', SignerStatus::Pending->value)->update(['status' => SignerStatus::Signed->value, 'signed_at' => $occurredAt]);
                    AuditLog::forProject($document->project, AuditAction::Sign, 'Documento', $document, $document->title, 'Envelope finalizado pelo provedor', visibleToClient: true, actorName: 'Provedor', actorType: 'system');
                    $this->onSigned($document);
                }
                if ($event['signed_file_url'] && ! $document->signed_file_path) {
                    DownloadSignedDocument::dispatch($document->id, $event['signed_file_url']);
                }
                break;
        }

        if ($document->type === DocumentType::DevelopmentContract) {
            $this->contracts->syncProjectStatus($document->project);
        }
    }

    /**
     * Sincronização ativa (caso um webhook se perca).
     */
    public function sync(Document $document): void
    {
        $state = $this->signatures->driver($document->provider)->fetch($document);

        foreach ($state['signed'] as $email) {
            $this->applyProviderEvent($document, ['event' => 'sign', 'signer_email' => $email, 'signer_id' => null, 'occurred_at' => null, 'signed_file_url' => null, 'evidence' => ['source' => 'sync']]);
        }
        foreach ($state['refused'] as $email) {
            $this->applyProviderEvent($document, ['event' => 'refusal', 'signer_email' => $email, 'signer_id' => null, 'occurred_at' => null, 'signed_file_url' => null, 'evidence' => ['source' => 'sync']]);
        }

        $mapped = match ($state['status']) {
            'closed' => 'close',
            'canceled', 'cancelled' => 'cancel',
            'expired' => 'deadline',
            default => null,
        };
        if ($mapped) {
            $this->applyProviderEvent($document->refresh(), ['event' => $mapped, 'signer_email' => null, 'signer_id' => null, 'occurred_at' => null, 'signed_file_url' => $state['signed_file_url'], 'evidence' => []]);
        }
    }

    public function storeSignedFile(Document $document, string $contents): void
    {
        $path = "documents/{$document->uuid}/v{$document->version}-assinado.pdf";
        Storage::disk(self::DISK)->put($path, $contents);
        $document->forceFill(['signed_file_path' => $path, 'signed_file_sha256' => hash('sha256', $contents)])->save();

        AuditLog::forProject($document->project, AuditAction::Update, 'Documento', $document, $document->title, 'PDF assinado arquivado (SHA-256 '.$document->signed_file_sha256.')', actorName: 'Sistema', actorType: 'system');
    }

    public function cancel(Document $document, string $reason): void
    {
        if ($document->isFinal()) {
            throw new InvalidArgumentException('Documento já finalizado.');
        }

        if ($document->provider_envelope_id) {
            $this->signatures->driver($document->provider)->cancel($document);
        }

        $document->forceFill(['status' => DocumentStatus::Cancelled, 'completed_at' => now()])->save();
        AuditLog::forProject($document->project, AuditAction::Cancel, 'Documento', $document, $document->title, $reason, visibleToClient: true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addSigner(Document $document, array $data): DocumentSigner
    {
        $this->assertEditable($document);

        return $document->signers()->create($data);
    }

    public function removeSigner(DocumentSigner $signer): void
    {
        $this->assertEditable($signer->document);
        $signer->delete();
    }

    protected function onSigned(Document $document): void
    {
        $document->loadMissing(['contract', 'scopeRequest']);

        if ($document->type === DocumentType::DevelopmentContract && $document->contract && $document->contract->status !== ContractStatus::Active) {
            $this->contracts->activate($document->contract, "Contrato assinado eletronicamente (documento {$document->uuid})");
        }

        // Termo de escopo adicional assinado = consentimento registrado pelo provedor.
        if ($document->type === DocumentType::ScopeChange && $document->scopeRequest && $document->scopeRequest->status === ScopeRequestStatus::Quoted) {
            $document->scopeRequest->forceFill([
                'status' => ScopeRequestStatus::Approved,
                'decided_at' => $document->completed_at,
                'decision_notes' => "Aprovado por assinatura eletrônica do documento {$document->uuid}",
                'decision_terms_hash' => $document->file_sha256,
            ])->save();
        }

        $this->notifier->client($document->client, "Documento assinado: {$document->title}", ['Todas as assinaturas foram concluídas. O PDF assinado ficará disponível no portal.'], 'Abrir portal', route('portal.documents.show', $document), 'documentos');
        $this->notifier->staff("Documento assinado: {$document->title}", 'Envelope finalizado pelo provedor.', route('admin.gestao.documents.show', $document), 'documentos');
    }

    /**
     * @param  array{signer_email: string|null, signer_id: string|null}  $event
     */
    protected function findSigner(Document $document, array $event): ?DocumentSigner
    {
        return $document->signers()
            ->where(fn ($query) => $query
                ->when($event['signer_id'], fn ($q) => $q->orWhere('provider_signer_id', $event['signer_id']))
                ->when($event['signer_email'], fn ($q) => $q->orWhere('email', $event['signer_email'])))
            ->first();
    }

    protected function addDefaultSigners(Document $document, ClientProject $project): void
    {
        $responsible = $project->client->contacts()->where('role', 'contract')->orderByDesc('is_primary')->first();

        if ($responsible?->email) {
            $document->signers()->create(['name' => $responsible->name, 'email' => $responsible->email, 'document_number' => $responsible->document, 'role' => 'client', 'sign_order' => 1]);
        }

        if (config('gestao.company.email')) {
            $document->signers()->create(['name' => config('gestao.company.name'), 'email' => config('gestao.company.email'), 'document_number' => config('gestao.company.document'), 'role' => 'contractor', 'sign_order' => 1]);
        }
    }

    protected function assertEditable(Document $document): void
    {
        if (! $document->isEditable()) {
            throw new InvalidArgumentException('Documento congelado ou finalizado não pode ser alterado; gere uma nova versão.');
        }
    }
}
