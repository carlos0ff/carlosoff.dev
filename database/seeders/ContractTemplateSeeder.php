<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

/**
 * Modelos iniciais dos 7 documentos. São rascunhos SEM revisão jurídica: precisam ser revisados por
 * profissional habilitado (e aprovados no painel) antes de qualquer envio para assinatura.
 * Idempotente. Atualiza o texto dos modelos base ainda NÃO revisados (nova versão); modelos já revisados
 * juridicamente ou criados/renomeados no painel nunca são alterados.
 */
class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DocumentType::cases() as $type) {
            $name = $type->label().' (modelo base)';
            $body = file_get_contents(__DIR__."/gestao-templates/{$type->value}.html");
            $existing = ContractTemplate::where('document_type', $type->value)->get();

            if ($existing->isEmpty()) {
                ContractTemplate::create(['name' => $name, 'document_type' => $type, 'body' => $body, 'is_active' => true]);

                continue;
            }

            $base = $existing->firstWhere('name', $name);
            if ($base && ! $base->isLegallyReviewed() && $base->body !== $body) {
                $base->forceFill(['body' => $body, 'version' => $base->version + 1])->save();
            }
        }
    }
}
