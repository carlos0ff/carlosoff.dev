<?php

namespace App\Console\Commands\Gestao;

use App\Models\Domain;
use App\Services\Monitoring\DomainExpiryChecker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestao:check-domains')]
#[Description('Atualiza a expiração dos domínios via RDAP')]
class CheckDomainsCommand extends Command
{
    public function handle(DomainExpiryChecker $checker): int
    {
        Domain::each(function (Domain $domain) use ($checker) {
            $expires = $checker->check($domain);
            $this->line(sprintf('%-40s %s', $domain->name, $expires?->format('d/m/Y') ?? 'sem resposta RDAP (mantida data manual)'));
        });

        return self::SUCCESS;
    }
}
