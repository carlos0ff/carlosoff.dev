<x-admin.card title="Domínio e hospedagem">
    @foreach ($project->domains as $domain)
        <div class="mb-4 text-sm">
            <p class="font-semibold">{{ $domain->name }} <span class="font-normal text-foreground-muted">· {{ $domain->registrar ?? 'registrador não informado' }}</span></p>
            <p class="text-foreground-secondary">Titular: {{ $domain->registrant_name ?? 'não informado' }}</p>
            <p class="text-xs text-foreground-muted">Administra: {{ \App\Models\Domain::PARTIES[$domain->managed_by] ?? $domain->managed_by }} · paga: {{ \App\Models\Domain::PARTIES[$domain->payer] ?? $domain->payer }}</p>
            <p class="mt-1 flex flex-wrap gap-1.5">
                <x-admin.badge :tone="$domain->expires_at && $domain->expires_at->lt(now()->addDays(30)) ? 'warning' : 'muted'">Domínio {{ $domain->expires_at?->format('d/m/Y') ?? '—' }}</x-admin.badge>
                <x-admin.badge :tone="$domain->ssl_expires_at && $domain->ssl_expires_at->lt(now()->addDays(21)) ? 'warning' : 'muted'">SSL {{ $domain->ssl_expires_at?->format('d/m/Y') ?? '—' }}</x-admin.badge>
            </p>
        </div>
    @endforeach
    @foreach ($project->hostingServices as $hosting)
        <div class="mb-4 text-sm">
            <p class="font-semibold">{{ $hosting->provider }} <span class="font-normal text-foreground-muted">{{ $hosting->plan }}</span></p>
            <p class="text-foreground-secondary">{{ \App\Support\Money::format($hosting->monthly_cost_cents) }}/mês · renova {{ $hosting->renews_at?->format('d/m/Y') ?? '—' }}</p>
            <p class="text-xs text-foreground-muted">Backup: {{ $hosting->backup_policy ?? 'não informado' }}</p>
        </div>
    @endforeach
    @if ($project->domains->isEmpty() && $project->hostingServices->isEmpty())
        <p class="mb-4 text-sm text-foreground-muted">Nada cadastrado. Registro do domínio e hospedagem são serviços distintos do software.</p>
    @endif
    <details class="border-t border-border-subtle pt-3">
        <summary class="cursor-pointer text-sm font-semibold text-primary-hover">+ Adicionar domínio</summary>
        <form method="POST" action="{{ route('admin.gestao.projects.domains.store', $project) }}" class="mt-3 space-y-3">
            @csrf
            <x-admin.input name="name" label="Domínio" required :value="$project->primary_domain" />
            <x-admin.input name="registrar" label="Registrador" />
            <x-admin.input name="registrant_name" label="Titular do registro" help="Quem consta no registro — não presuma que é a sua empresa." />
            <x-admin.input name="registrant_document" label="CPF/CNPJ do titular" />
            <x-admin.select name="managed_by" label="Administrado por" :options="\App\Models\Domain::PARTIES" />
            <x-admin.select name="payer" label="Responsável pelo pagamento" :options="\App\Models\Domain::PARTIES" />
            <x-admin.input name="registered_at" label="Registro" type="date" />
            <x-admin.input name="expires_at" label="Expiração" type="date" />
            <x-admin.toggle name="auto_renew" label="Renovação automática" />
            <x-admin.button type="submit" size="sm">Salvar domínio</x-admin.button>
        </form>
    </details>
    <details class="mt-3 border-t border-border-subtle pt-3">
        <summary class="cursor-pointer text-sm font-semibold text-primary-hover">+ Adicionar hospedagem</summary>
        <form method="POST" action="{{ route('admin.gestao.projects.hosting.store', $project) }}" class="mt-3 space-y-3">
            @csrf
            <x-admin.input name="provider" label="Provedor" required />
            <x-admin.input name="plan" label="Plano" />
            <x-admin.textarea name="resources" label="Recursos contratados" :rows="2" />
            <x-admin.select name="managed_by" label="Administrada por" :options="\App\Models\Domain::PARTIES" value="us" />
            <x-admin.select name="payer" label="Responsável pelo pagamento" :options="\App\Models\Domain::PARTIES" />
            <x-admin.input name="monthly_cost" label="Custo mensal (R$)" />
            <x-admin.input name="renews_at" label="Renovação" type="date" />
            <x-admin.textarea name="limits_and_responsibilities" label="Limites e responsabilidades" :rows="2" />
            <x-admin.textarea name="backup_policy" label="Política de backup e recuperação" :rows="2" />
            <x-admin.textarea name="migration_procedure" label="Migração e encerramento" :rows="2" />
            <x-admin.button type="submit" size="sm">Salvar hospedagem</x-admin.button>
        </form>
    </details>
</x-admin.card>
