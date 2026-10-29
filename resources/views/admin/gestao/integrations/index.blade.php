<x-layouts.admin title="API e integrações" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'API e integrações' => null]">
    <x-admin.page-header title="API e integrações" subtitle="Credenciais por instalação, automação administrativa, assinatura eletrônica e pagamentos." />

    @if (session('issued_secret'))
        @php($issued = session('issued_secret'))
        <x-admin.card title="Credencial emitida — copie agora" class="mb-6 border-warning-border">
            <pre class="overflow-x-auto rounded bg-background p-3 text-xs" data-issued-secret>KEY_ID={{ $issued['key_id'] }}
SECRET={{ $issued['secret'] }}</pre>
            <p class="mt-2 text-xs text-warning">O segredo não será exibido novamente. Nunca o coloque em código de frontend.</p>
        </x-admin.card>
    @endif

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Assinatura eletrônica">
            <x-admin.kv class="!grid-cols-1" :items="[
                'Provedor' => e($signature['driver']),
                'API' => $signature['configured'] ? '<span class=\'text-success\'>token configurado</span>' : '<span class=\'text-warning\'>CLICKSIGN_ACCESS_TOKEN ausente</span>',
                'Endpoint' => e($signature['base_url']),
                'Segredo do webhook' => $signature['webhook_secret'] ? '<span class=\'text-success\'>configurado</span>' : '<span class=\'text-warning\'>CLICKSIGN_WEBHOOK_SECRET ausente (webhooks serão rejeitados)</span>',
                'URL do webhook' => '<code class=\'text-xs break-all\'>'.e($signature['webhook_url']).'</code>',
                'Último evento' => $signature['last_event'] ? e($signature['last_event']->event).' em '.$signature['last_event']->created_at->format('d/m/Y H:i') : null,
            ]" />
        </x-admin.card>
        <x-admin.card title="Pagamentos">
            <x-admin.kv class="!grid-cols-1" :items="[
                'Gateway' => e($payments['driver']),
                'Confirmação' => $payments['driver'] === 'manual' ? 'Baixa manual com responsável e evidência obrigatória.' : 'Webhook com assinatura validada.',
                'Webhook' => '<code class=\'text-xs\'>'.e(url('/api/webhooks/pagamento/{provedor}')).'</code>',
            ]" />
        </x-admin.card>
        <x-admin.card title="API de gerenciamento">
            <x-admin.kv class="!grid-cols-1" :items="[
                'Base' => '<code class=\'text-xs\'>'.e(url('/api/v1')).'</code>',
                'Autenticação' => 'HMAC-SHA256 + timestamp + nonce',
                'HTTPS obrigatório' => $apiHttps ? 'Sim' : '<span class=\'text-warning\'>Não (somente local/testes)</span>',
                'Limite' => config('gestao.api.rate_limit_per_minute').' req/min por credencial',
                'Documentação' => 'docs/gestao/API.md',
            ]" />
        </x-admin.card>
    </div>

    <x-admin.card title="Credenciais" :padding="false" class="mb-6">
        <x-admin.table>
            <thead><tr><th>Key ID</th><th>Tipo</th><th>Escopo</th><th>Permissões</th><th>Último uso</th><th>Situação</th><th></th></tr></thead>
            <tbody>
                @forelse ($credentials as $credential)
                    <tr>
                        <td data-label="Key ID"><code class="text-xs">{{ $credential->key_id }}</code><span class="block text-xs text-foreground-muted">{{ $credential->name }}</span></td>
                        <td data-label="Tipo">{{ $credential->kind->label() }}</td>
                        <td data-label="Escopo">{{ $credential->project?->name ?? ($credential->user?->name ? 'Equipe: '.$credential->user->name : '—') }}</td>
                        <td data-label="Permissões" class="text-xs">{{ implode(', ', $credential->abilities) }}</td>
                        <td data-label="Último uso" class="text-xs">{{ $credential->last_used_at?->format('d/m/Y H:i') ?? 'nunca' }} {{ $credential->last_used_ip }}</td>
                        <td data-label="Situação">@if ($credential->revoked_at)<x-admin.badge tone="danger">revogada</x-admin.badge>@elseif (! $credential->isUsable())<x-admin.badge>expirada</x-admin.badge>@elseif ($credential->expires_at)<x-admin.badge tone="warning">expira {{ $credential->expires_at->format('d/m H:i') }}</x-admin.badge>@else<x-admin.badge tone="success">ativa</x-admin.badge>@endif</td>
                        <td data-label="">
                            @if ($credential->isUsable())
                                <form method="POST" action="{{ route('admin.gestao.credentials.revoke', $credential) }}" class="flex gap-1">@csrf<input name="reason" required placeholder="Motivo" class="w-24 rounded-md border border-border bg-background px-2 text-xs"><x-admin.button type="submit" size="sm" variant="danger">Revogar</x-admin.button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-foreground-muted">Nenhuma credencial. Emita credenciais de instalação na página de cada projeto.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.card>

    @can('gestao.admin')
        <x-admin.card title="Nova credencial de automação administrativa">
            <p class="mb-3 text-sm text-foreground-secondary">Para scripts da equipe (ex.: emitir comandos via API). Pertence ao seu usuário e pode acessar todos os projetos.</p>
            <form method="POST" action="{{ route('admin.gestao.integrations.automation.store') }}" class="space-y-3">
                @csrf
                <x-admin.input name="name" label="Nome" required />
                <fieldset class="grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Models\ApiCredential::ABILITIES as $ability => $label)
                        @continue(in_array($ability, ['health:write', 'commands:pull'], true))
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="abilities[]" value="{{ $ability }}"> {{ $label }} <code class="text-xs text-foreground-muted">{{ $ability }}</code></label>
                    @endforeach
                </fieldset>
                <x-admin.button type="submit" size="sm">Emitir</x-admin.button>
            </form>
        </x-admin.card>
    @endcan
</x-layouts.admin>
