<?php

namespace App\Console\Commands\Gestao;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\Contracts\DocumentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('gestao:sync-signatures')]
#[Description('Consulta o provedor para documentos aguardando assinatura (rede de segurança caso um webhook se perca)')]
class SyncSignaturesCommand extends Command
{
    public function handle(DocumentService $documents): int
    {
        Document::whereNotNull('provider_envelope_id')
            ->whereIn('status', [DocumentStatus::AwaitingSignature->value, DocumentStatus::PartiallySigned->value])
            ->orWhere(fn ($q) => $q->where('status', DocumentStatus::Signed->value)->whereNull('signed_file_path')->whereNotNull('provider_envelope_id'))
            ->each(function (Document $document) use ($documents) {
                try {
                    $documents->sync($document);
                    $this->line("{$document->uuid}: {$document->refresh()->status->label()}");
                } catch (Throwable $exception) {
                    $this->warn("{$document->uuid}: {$exception->getMessage()}");
                }
            });

        return self::SUCCESS;
    }
}
