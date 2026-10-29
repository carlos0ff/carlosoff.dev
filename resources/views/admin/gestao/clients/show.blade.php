@php
    $money = fn ($cents) => \App\Support\Money::format($cents);
    $openCharges = $charges->filter(fn ($c) => $c->isOpen());
    $tabs = [
        'dados' => 'Cadastro',
        'projetos' => ['Projetos e contratos', $client->projects->count() ?: null],
        'financeiro' => ['Financeiro', $openCharges->count() ?: null],
        'contatos' => ['Contatos e portal', $client->contacts->count() ?: null],
        'historico' => 'Histórico',
    ];
    $tab = array_key_exists(request('aba'), $tabs) ? request('aba') : 'dados';
@endphp
<x-layouts.admin :title="$client->legal_name" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Clientes' => route('admin.gestao.clients.index'), $client->legal_name => null]">
    <x-admin.page-header :title="$client->displayName()" :subtitle="$client->legal_name.' · '.(\App\Support\BrazilianDocument::format($client->document) ?: 'sem documento').' · cliente desde '.($client->relationship_started_at?->format('m/Y') ?? '—')">
        <x-admin.badge :tone="$client->status->tone()">● {{ $client->status->label() }}</x-admin.badge>
        <x-admin.button :href="route('admin.gestao.clients.edit', $client)">Editar</x-admin.button>
        <x-admin.button :href="route('admin.gestao.projects.create', ['client' => $client->id])" variant="primary">+ Novo projeto</x-admin.button>
    </x-admin.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Projetos" :value="$client->projects->count()" :hint="$client->projects->where('operational_status.value', 'active')->count().' em operação'" />
        <x-admin.stat label="Receita recorrente" :value="$money($contracts->where('status.value', 'active')->sum('monthly_fee_cents'))" hint="contratos vigentes" />
        <x-admin.stat label="Em aberto" :value="$money($openCharges->sum(fn ($c) => $c->outstandingCents()))" :hint="$openCharges->count().' cobrança(s)'" />
        <x-admin.stat label="Acessos ao portal" :value="$client->portalUsers->count()" :hint="$client->portalUsers->max('last_login_at')?->diffForHumans() ? 'último acesso '.$client->portalUsers->max('last_login_at')->diffForHumans() : 'nenhum acesso ainda'" />
    </div>

    <x-admin.tabs :tabs="$tabs" :current="$tab" label="Seções do cliente" />

    @if (session('portal_link'))
        <x-admin.card title="Link de acesso ao portal" class="mb-6 border-success-border">
            <p class="text-sm">Envie este link ao cliente para que ele defina a própria senha (expira em 60 minutos):</p>
            <code class="mt-2 block break-all rounded bg-background p-3 text-xs" data-portal-link>{{ session('portal_link') }}</code>
        </x-admin.card>
    @endif

    @if ($tab === 'dados')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Dados cadastrais">
                <x-admin.kv :items="[
                    'E-mail' => e($client->email),
                    'Telefone' => e($client->phone),
                    'WhatsApp' => e($client->whatsapp),
                    'E-mail de cobrança' => e($client->billing_email),
                    'Endereço' => e(collect([$client->address_street, $client->address_number, $client->address_district, $client->address_city, $client->address_state, $client->address_zip])->filter()->implode(', ')),
                    'Cliente desde' => $client->relationship_started_at?->format('d/m/Y'),
                    'Dados de cobrança' => nl2br(e($client->billing_notes)),
                    'Observações internas' => $client->internal_notes ? '<span class=\'text-warning\'>(interno)</span> '.nl2br(e($client->internal_notes)) : null,
                ]" />
            </x-admin.card>

            </div>
            <div class="space-y-6">
            @include('admin.gestao.partials.attachments', ['type' => 'client', 'model' => $client, 'attachments' => $client->attachments])
            @can('gestao.admin')
                <x-admin.card title="Dados pessoais (LGPD)">
                    <p class="mb-3 text-sm text-foreground-secondary">Exporte os dados do cliente ou anonimize após o encerramento. Registros financeiros e documentos assinados são mantidos pelo prazo legal.</p>
                    <x-admin.button size="sm" :href="route('admin.gestao.clients.export', $client)">Exportar dados (JSON)</x-admin.button>
                    @unless ($client->anonymized_at)
                        <form method="POST" action="{{ route('admin.gestao.clients.anonymize', $client) }}" class="mt-4 space-y-2" data-confirm="Os dados pessoais serão substituídos de forma irreversível." data-confirm-title="Anonimizar cliente?" data-confirm-button="Anonimizar">
                            @csrf
                            <x-admin.input name="confirmation" label="Digite o identificador para confirmar" :help="$client->uuid" />
                            <x-admin.button type="submit" size="sm" variant="danger">Anonimizar</x-admin.button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-warning">Anonimizado em {{ $client->anonymized_at->format('d/m/Y') }}.</p>
                    @endunless
                </x-admin.card>
            @endcan
            </div>
        </div>
    @elseif ($tab === 'projetos')
        <div class="grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Projetos" :padding="false">
                @forelse ($client->projects as $project)
                    <a href="{{ route('admin.gestao.projects.show', $project) }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0 hover:bg-card-hover">
                        <span class="font-semibold">{{ $project->name }}</span>
                        <span class="flex flex-wrap gap-1"><x-admin.status :value="$project->development_status" /><x-admin.status :value="$project->financial_status" /><x-admin.status :value="$project->operational_status" /></span>
                    </a>
                @empty
                    <p class="p-5 text-sm text-foreground-muted">Nenhum projeto.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Contratos" :padding="false">
                @forelse ($contracts as $contract)
                    <a href="{{ route('admin.gestao.contracts.show', $contract) }}" class="flex items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0 hover:bg-card-hover">
                        <span><span class="font-semibold">{{ $contract->number }}</span> — {{ $contract->project->name }}</span><x-admin.status :value="$contract->status" />
                    </a>
                @empty
                    <p class="p-5 text-sm text-foreground-muted">Nenhum contrato.</p>
                @endforelse
            </x-admin.card>

        </div>
    @elseif ($tab === 'financeiro')
        <x-admin.card title="Últimas cobranças" :padding="false">
            @forelse ($charges as $charge)
                <a href="{{ route('admin.gestao.charges.show', $charge) }}" class="flex items-center justify-between gap-2 border-b border-border-subtle px-5 py-3 text-sm last:border-0 hover:bg-card-hover">
                    <span class="min-w-0 truncate">{{ $charge->due_date->format('d/m/Y') }} — {{ $charge->description }}</span>
                    <span class="flex shrink-0 items-center gap-2 tabular-nums">{{ $money($charge->amount_cents) }} <x-admin.status :value="$charge->status" /></span>
                </a>
            @empty
                <p class="p-5 text-sm text-foreground-muted">Nenhuma cobrança.</p>
            @endforelse
        </x-admin.card>

    @elseif ($tab === 'contatos')
        <div class="grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Contatos">
                <ul class="mb-4 space-y-3 text-sm">
                    @forelse ($client->contacts as $contact)
                        <li class="flex items-start justify-between gap-2">
                            <span><strong>{{ $contact->name }}</strong> @if ($contact->is_primary)<x-admin.badge tone="primary">principal</x-admin.badge>@endif<br>
                                <span class="text-foreground-secondary">{{ $contact->role->label() }}{{ $contact->position ? ' · '.$contact->position : '' }}</span><br>
                                <span class="text-foreground-muted">{{ $contact->email }} {{ $contact->phone }}</span></span>
                            <form method="POST" action="{{ route('admin.gestao.clients.contacts.destroy', [$client, $contact]) }}" data-confirm="Remover {{ $contact->name }}?" data-confirm-title="Remover contato?" data-confirm-button="Remover">@csrf @method('DELETE')<x-admin.icon-button label="Remover" danger>✕</x-admin.icon-button></form>
                        </li>
                    @empty
                        <li class="text-foreground-muted">Nenhum contato. Cadastre o responsável pelo contrato: ele será o signatário padrão.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.gestao.clients.contacts.store', $client) }}" class="space-y-3 border-t border-border-subtle pt-4">
                    @csrf
                    <x-admin.input name="name" label="Nome" required />
                    <x-admin.select name="role" label="Função" :options="\App\Enums\ContactRole::options()" />
                    <x-admin.input name="position" label="Cargo" />
                    <x-admin.input name="email" label="E-mail" type="email" />
                    <x-admin.input name="phone" label="Telefone" />
                    <x-admin.input name="document" label="CPF" help="Usado para identificar o signatário." />
                    <x-admin.toggle name="is_primary" label="Contato principal" />
                    <x-admin.button type="submit" size="sm">Adicionar contato</x-admin.button>
                </form>
            </x-admin.card>

            <x-admin.card title="Acesso ao portal">
                <ul class="mb-4 space-y-1 text-sm">
                    @forelse ($client->portalUsers as $user)
                        <li>{{ $user->name }} — <span class="text-foreground-muted">{{ $user->email }}</span></li>
                    @empty
                        <li class="text-foreground-muted">Ninguém com acesso ainda.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.gestao.clients.portal-users.store', $client) }}" class="space-y-3">
                    @csrf
                    <x-admin.input name="name" label="Nome" required />
                    <x-admin.input name="email" label="E-mail" type="email" required />
                    <x-admin.button type="submit" size="sm">Criar acesso</x-admin.button>
                </form>
            </x-admin.card>

        </div>
    @else
        <x-admin.card title="Histórico">
            <ul class="space-y-2 text-sm">
                @forelse ($history as $log)
                    <li><span class="text-foreground-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span> · {{ $log->user_name }} {{ mb_strtolower($log->action->label()) }} {{ $log->resource_type }} <strong>{{ $log->resource_label }}</strong> @if ($log->details)<span class="text-foreground-muted">({{ $log->details }})</span>@endif</li>
                @empty
                    <li class="text-foreground-muted">Sem registros.</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
    @endif
</x-layouts.admin>
