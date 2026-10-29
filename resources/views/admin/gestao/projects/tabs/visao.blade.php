<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Projeto">
            <x-admin.kv :items="[
                'Cliente' => '<a class=\'font-medium hover:text-primary-hover\' href=\''.route('admin.gestao.clients.show', $project->client).'\'>'.e($project->client->legal_name).'</a>',
                'Responsável técnico' => e($project->technicalLead?->name),
                'Tecnologias' => e($project->technologies),
                'Repositório' => $project->repository_url ? '<a class=\'hover:text-primary-hover\' target=\'_blank\' rel=\'noopener\' href=\''.e($project->repository_url).'\'>'.e($project->repository_url).'</a>' : null,
                'Descrição' => nl2br(e($project->description)),
                'Objetivo' => nl2br(e($project->objective)),
            ]" />
        </x-admin.card>

        <x-admin.card title="Ambientes e prazos">
            <x-admin.kv :items="[
                'Domínio principal' => e($project->primary_domain),
                'Versão implantada' => $project->deployed_version ? '<code class=\'text-xs\'>'.e($project->deployed_version).'</code>' : null,
                'Homologação' => $project->staging_url ? '<a class=\'hover:text-primary-hover\' target=\'_blank\' rel=\'noopener\' href=\''.e($project->staging_url).'\'>'.e($project->staging_url).'</a>' : null,
                'Produção' => $project->production_url ? '<a class=\'hover:text-primary-hover\' target=\'_blank\' rel=\'noopener\' href=\''.e($project->production_url).'\'>'.e($project->production_url).'</a>' : null,
                'Início' => $project->started_at?->format('d/m/Y'),
                'Entrega prevista' => $project->due_at?->format('d/m/Y').($project->due_at && $project->due_at->isPast() && $project->development_status->value !== 'completed' ? ' <span class=\'text-warning\'>(atrasada)</span>' : ''),
                'Publicação' => $project->published_at?->format('d/m/Y'),
            ]" />
        </x-admin.card>

        @if ($project->internal_notes)
            <x-admin.card title="Observações internas">
                <p class="whitespace-pre-line text-sm text-foreground-secondary">{{ $project->internal_notes }}</p>
                <p class="mt-3 text-xs text-foreground-muted">Nunca exibidas no portal do cliente.</p>
            </x-admin.card>
        @endif
    </div>

    <div class="space-y-6">
        <x-admin.card title="Plano contratado">
            <x-admin.kv class="!grid-cols-1" :items="[
                'Plano' => e($project->plan_name),
                'Implantação' => $money($project->setup_fee_cents),
                'Mensalidade' => $money($project->monthly_fee_cents).($project->billing_day ? ' · vence dia '.$project->billing_day : ''),
                'Contrato vigente' => $project->activeContract ? '<a class=\'font-medium hover:text-primary-hover\' href=\''.route('admin.gestao.contracts.show', $project->activeContract).'\'>'.e($project->activeContract->number).'</a>' : '<span class=\'text-warning\'>nenhum</span>',
            ]" />
        </x-admin.card>

        <x-admin.card title="Disponibilidade">
            @if ($check = $project->latestHealthCheck)
                <p class="flex items-center gap-2 text-sm"><span @class(['h-2.5 w-2.5 rounded-full', 'bg-success' => $check->is_up, 'bg-error' => ! $check->is_up])></span><strong>{{ $check->is_up ? 'Disponível' : 'Com falha' }}</strong> <span class="text-foreground-muted">· {{ $check->source === 'agent' ? 'agente' : 'verificação externa' }} · {{ $check->checked_at->diffForHumans() }}</span></p>
            @else
                <p class="text-sm text-foreground-muted">Ainda sem verificações.</p>
            @endif
            <p class="mt-2 text-sm text-foreground-secondary">Agente: {{ $project->agent_last_seen_at ? 'último contato '.$project->agent_last_seen_at->diffForHumans() : 'não instalado' }}</p>
            <a href="{{ request()->fullUrlWithQuery(['aba' => 'operacao']) }}" class="mt-3 inline-block text-xs font-semibold text-primary-hover hover:underline">Ver operação →</a>
        </x-admin.card>

        @include('admin.gestao.projects.partials.domain-hosting')
        @include('admin.gestao.partials.attachments', ['type' => 'project', 'model' => $project, 'attachments' => $project->attachments])
    </div>
</div>
