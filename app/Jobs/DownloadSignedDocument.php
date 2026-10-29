<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Contracts\DocumentService;
use App\Services\Signature\SignatureManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadSignedDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function __construct(public int $documentId, public string $url) {}

    public function handle(SignatureManager $signatures, DocumentService $documents): void
    {
        $document = Document::findOrFail($this->documentId);

        if ($document->signed_file_path) {
            return;
        }

        $documents->storeSignedFile($document, $signatures->driver($document->provider)->download($this->url));
    }
}
