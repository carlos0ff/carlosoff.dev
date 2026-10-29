<?php

namespace App\Console\Commands\Gestao;

use App\Services\Billing\RecurringChargeGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestao:generate-charges')]
#[Description('Gera as mensalidades dos contratos vigentes (idempotente por competência)')]
class GenerateChargesCommand extends Command
{
    public function handle(RecurringChargeGenerator $generator): int
    {
        $created = $generator->generate();
        $this->components->info(count($created).' cobrança(s) recorrente(s) gerada(s).');

        return self::SUCCESS;
    }
}
