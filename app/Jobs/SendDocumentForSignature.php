<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Contracts\DocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendDocumentForSignature implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function __construct(public int $documentId) {}

    public function handle(DocumentService $documents): void
    {
        $documents->performSend(Document::findOrFail($this->documentId));
    }

    public function failed(Throwable $exception): void
    {
        Document::whereKey($this->documentId)->update(['provider_error' => mb_substr($exception->getMessage(), 0, 250)]);
    }
}
