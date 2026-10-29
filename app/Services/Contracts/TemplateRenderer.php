<?php

namespace App\Services\Contracts;

use App\Enums\TicketPriority;
use App\Models\BillingPolicy;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\Domain;
use App\Models\HostingService;
use App\Models\ProjectValidation;
use App\Models\ScopeRequest;
use App\Support\BrazilianDocument;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Preenche modelos com `{{ variavel }}` a partir dos cadastros. Valores são escapados; campos vazios ficam
 * destacados para revisão (nunca inventados).
 */
class TemplateRenderer
{
    /**
     * @return array<string, string|null>
     */
    public function variables(ClientProject $project, ?Contract $contract = null, ?ScopeRequest $scope = null, ?ProjectValidation $validation = null): array
    {
        $client = $project->client;
        $contract ??= $project->activeContract ?? $project->contracts()->latest('id')->first();
        $policy = $contract?->policy;
        $domain = $project->domains()->first();
        $hosting = $project->hostingServices()->first();
        $responsible = $client->contacts()->where('role', 'contract')->orderByDesc('is_primary')->first();
        $date = fn (?Carbon $value) => $value?->format('d/m/Y');

        $vars = [
            'data.hoje' => now()->format('d/m/Y'),
            'empresa.nome' => config('gestao.company.name'),
            'empresa.documento' => config('gestao.company.document') ? BrazilianDocument::format(config('gestao.company.document')) : null,
            'empresa.email' => config('gestao.company.email'),
            'empresa.cidade' => config('gestao.company.city'),

            'cliente.razao_social' => $client->legal_name,
            'cliente.nome_fantasia' => $client->trade_name,
            'cliente.documento' => $client->document ? BrazilianDocument::format($client->document) : null,
            'cliente.email' => $client->email,
            'cliente.telefone' => $client->phone,
            'cliente.endereco' => $this->address($client),
            'cliente.responsavel' => $responsible?->name,
            'cliente.responsavel_email' => $responsible?->email,
            'cliente.responsavel_documento' => $responsible?->document ? BrazilianDocument::format($responsible->document) : null,

            'projeto.nome' => $project->name,
            'projeto.descricao' => $project->description,
            'projeto.objetivo' => $project->objective,
            'projeto.tecnologias' => $project->technologies,
            'projeto.dominio' => $project->primary_domain,
            'projeto.url_homologacao' => $project->staging_url,
            'projeto.url_producao' => $project->production_url,
            'projeto.inicio' => $date($project->started_at),
            'projeto.entrega_prevista' => $date($project->due_at),
            'projeto.plano' => $project->plan_name,
            'projeto.versao_implantada' => $project->deployed_version,
        ];

        if ($contract) {
            $vars += [
                'contrato.numero' => $contract->number,
                'contrato.objeto' => $contract->object,
                'contrato.escopo' => $contract->scope,
                'contrato.entregaveis' => $contract->deliverables,
                'contrato.valor_implantacao' => Money::format($contract->setup_fee_cents),
                'contrato.mensalidade' => $contract->monthly_fee_cents ? Money::format($contract->monthly_fee_cents) : 'Não há',
                'contrato.forma_pagamento' => $contract->payment_method,
                'contrato.condicoes_pagamento' => $contract->payment_terms,
                'contrato.dia_vencimento' => $contract->billing_day ? (string) $contract->billing_day : null,
                'contrato.inicio' => $date($contract->starts_at),
                'contrato.fim' => $date($contract->ends_at) ?? 'Prazo indeterminado',
                'contrato.renovacao' => $contract->renewal_rules,
                'contrato.sla' => $this->sla($contract),
            ];
            foreach (array_keys(Contract::CLAUSES) as $key) {
                $vars["contrato.clausula.{$key}"] = $contract->clause($key);
            }
        }

        if ($policy instanceof BillingPolicy) {
            $vars += [
                'politica.lembrete_dias' => (string) $policy->reminder_days_before,
                'politica.tolerancia_dias' => (string) $policy->grace_days,
                'politica.aviso_previo_dias' => (string) $policy->pre_suspension_notice_days,
                'politica.permite_suspensao' => $policy->suspension_allowed ? 'Sim' : 'Não',
                'politica.forma_restricao' => $policy->restriction_mode->label(),
                'politica.reativacao' => $policy->reactivation_mode->label(),
                'politica.clausula' => $policy->contract_clause_reference,
                'politica.regularizacao' => $policy->regularization_instructions,
            ];
        }

        if ($validation) {
            $vars += [
                'validacao.versao' => $validation->version,
                'validacao.ambiente' => $validation->environment,
                'validacao.url' => $validation->environment_url,
                'validacao.funcionalidades' => $validation->features_evaluated,
                'validacao.testes' => $validation->tests_performed,
                'validacao.pendencias' => $validation->known_issues ?: 'Nenhuma pendência conhecida informada.',
                'validacao.evidencias' => $validation->evidence,
                'validacao.resultado' => $validation->outcome->label(),
                'validacao.data' => $date($validation->responded_at),
                'validacao.responsavel' => $validation->responder?->name,
                'validacao.observacoes' => $validation->client_notes,
            ];
        }

        if ($scope) {
            $vars += [
                'solicitacao.numero' => (string) $scope->id,
                'solicitacao.titulo' => $scope->title,
                'solicitacao.descricao' => $scope->description,
                'solicitacao.motivo' => $scope->reason,
                'solicitacao.funcionalidades_afetadas' => $scope->affected_features,
                'solicitacao.impacto' => $scope->impact_analysis,
                'solicitacao.horas' => $scope->estimated_hours !== null ? number_format((float) $scope->estimated_hours, 1, ',', '.') : null,
                'solicitacao.valor' => $scope->amount_cents !== null ? Money::format($scope->amount_cents) : null,
                'solicitacao.prazo_dias' => $scope->estimated_days !== null ? (string) $scope->estimated_days : null,
                'solicitacao.dependencias' => $scope->dependencies,
                'solicitacao.criterios' => $scope->acceptance_criteria,
            ];
        }

        if ($domain instanceof Domain) {
            $vars += [
                'dominio.nome' => $domain->name,
                'dominio.registrador' => $domain->registrar,
                'dominio.titular' => $domain->registrant_name,
                'dominio.titular_documento' => $domain->registrant_document ? BrazilianDocument::format($domain->registrant_document) : null,
                'dominio.administrado_por' => Domain::PARTIES[$domain->managed_by] ?? $domain->managed_by,
                'dominio.pagador' => Domain::PARTIES[$domain->payer] ?? $domain->payer,
                'dominio.registro' => $date($domain->registered_at),
                'dominio.expiracao' => $date($domain->expires_at),
                'dominio.renovacao_automatica' => $domain->auto_renew ? 'Sim' : 'Não',
            ];
        }

        if ($hosting instanceof HostingService) {
            $vars += [
                'hospedagem.provedor' => $hosting->provider,
                'hospedagem.plano' => $hosting->plan,
                'hospedagem.recursos' => $hosting->resources,
                'hospedagem.administrada_por' => Domain::PARTIES[$hosting->managed_by] ?? $hosting->managed_by,
                'hospedagem.pagador' => Domain::PARTIES[$hosting->payer] ?? $hosting->payer,
                'hospedagem.custo_mensal' => Money::format($hosting->monthly_cost_cents),
                'hospedagem.renovacao' => $date($hosting->renews_at),
                'hospedagem.limites' => $hosting->limits_and_responsibilities,
                'hospedagem.backup' => $hosting->backup_policy,
                'hospedagem.migracao' => $hosting->migration_procedure,
            ];
        }

        return $vars;
    }

    /**
     * @param  array<string, string|null>  $variables
     */
    public function render(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', function (array $match) use ($variables) {
            $value = $variables[$match[1]] ?? null;

            if ($value === null || trim($value) === '') {
                return '<mark class="missing">[preencher: '.e($match[1]).']</mark>';
            }

            return nl2br(e($value));
        }, $template) ?? $template;
    }

    /**
     * Variáveis do modelo que ficaram sem valor.
     *
     * @param  array<string, string|null>  $variables
     * @return list<string>
     */
    public function missing(string $template, array $variables): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', $template, $matches);

        return array_values(array_unique(array_filter($matches[1], fn (string $key) => blank($variables[$key] ?? null))));
    }

    protected function address(Client $client): ?string
    {
        $line = collect([$client->address_street, $client->address_number, $client->address_complement, $client->address_district])->filter()->implode(', ');
        $city = collect([$client->address_city, $client->address_state])->filter()->implode('/');

        return collect([$line, $city, $client->address_zip ? "CEP {$client->address_zip}" : null])->filter()->implode(' — ') ?: null;
    }

    protected function sla(Contract $contract): ?string
    {
        if (empty($contract->support_sla)) {
            return null;
        }

        return collect($contract->support_sla)->map(function (array $times, string $priority) {
            $label = TicketPriority::tryFrom($priority)?->label() ?? $priority;

            return "{$label}: resposta em até {$times['response_hours']}h úteis; solução estimada em até {$times['resolution_hours']}h úteis (prazo de solução é estimativa, não garantia).";
        })->implode("\n");
    }
}
