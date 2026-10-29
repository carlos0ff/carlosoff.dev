<?php

namespace App\Console\Commands\Gestao;

use App\Models\ClientProject;
use App\Services\Api\CredentialService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestao:credential {project : UUID do projeto} {name=agente : Nome da instalação}')]
#[Description('Emite uma credencial de instalação para o agente de um projeto (o segredo é exibido uma única vez)')]
class IssueCredentialCommand extends Command
{
    public function handle(CredentialService $credentials): int
    {
        $project = ClientProject::where('uuid', $this->argument('project'))->first();
        if (! $project) {
            $this->components->error('Projeto não encontrado.');

            return self::FAILURE;
        }

        $issued = $credentials->issueForInstallation($project, (string) $this->argument('name'));

        $this->components->warn('Guarde o segredo agora: ele não será exibido novamente.');
        $this->line('CENTRAL_API_URL='.url('/api/v1'));
        $this->line("CENTRAL_PROJECT_ID={$project->uuid}");
        $this->line("CENTRAL_KEY_ID={$issued['credential']->key_id}");
        $this->line("CENTRAL_SECRET={$issued['secret']}");

        return self::SUCCESS;
    }
}
