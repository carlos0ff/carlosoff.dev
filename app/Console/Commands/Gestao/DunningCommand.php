<?php

namespace App\Console\Commands\Gestao;

use App\Services\Billing\DunningService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestao:dunning')]
#[Description('Executa a régua de cobrança conforme a política de cada contrato (avisos, vencimentos e propostas de suspensão)')]
class DunningCommand extends Command
{
    public function handle(DunningService $dunning): int
    {
        $stats = $dunning->process();
        $this->components->info("Cobranças avaliadas: {$stats['processed']} · avisos: {$stats['notices']} · propostas de suspensão: {$stats['proposals']}.");

        return self::SUCCESS;
    }
}
