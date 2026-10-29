<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\Contracts\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends PortalController
{
    public function index(Request $request): View
    {
        return view('portal.documents.index', [
            'documents' => Document::where('client_id', $this->clientId($request))->where('visible_to_client', true)
                ->whereNotIn('status', ['draft', 'under_review'])->with('project')->latest()->paginate(20),
        ]);
    }

    public function show(Request $request, Document $document): View
    {
        $this->ensureVisible($request, $document);

        return view('portal.documents.show', ['document' => $document->load(['signers', 'project'])]);
    }

    public function download(Request $request, Document $document, string $variant): StreamedResponse
    {
        $this->ensureVisible($request, $document);
        $path = $variant === 'original' ? $document->file_path : $document->signed_file_path;
        abort_unless($path && Storage::disk(DocumentService::DISK)->exists($path), 404);

        AuditLog::record(AuditAction::Access, 'Documento', $document->id, $document->title, "Download ({$variant}) pelo cliente", ['client_id' => $document->client_id, 'client_project_id' => $document->client_project_id]);

        return Storage::disk(DocumentService::DISK)->download($path, "{$variant}-{$document->uuid}.pdf");
    }

    protected function ensureVisible(Request $request, Document $document): void
    {
        $this->ensureOwned($request, $document);
        abort_unless($document->visible_to_client && ! in_array($document->status->value, ['draft', 'under_review'], true), 404);
    }
}
