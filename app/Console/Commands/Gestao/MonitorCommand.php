<?php

namespace App\Console\Commands\Gestao;

use App\Jobs\ProbeProjectHealth;
use App\Models\ClientProject;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestao:monitor')]
#[Description('Agenda as verificações de saúde (HTTP + SSL) dos projetos monitorados')]
class MonitorCommand extends Command
{
    public function handle(): int
    {
        $count = 0;
        ClientProject::where('monitoring_enabled', true)->whereNotNull('production_url')->each(function (ClientProject $project) use (&$count) {
            ProbeProjectHealth::dispatch($project->id);
            $count++;
        });

        $this->components->info("{$count} verificação(ões) agendada(s).");

        return self::SUCCESS;
    }
}
