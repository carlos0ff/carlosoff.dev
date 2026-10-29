<x-layouts.admin title="Documentos e assinaturas" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Documentos' => null]">
    <x-admin.page-header title="Documentos e assinaturas" :subtitle="$counts['all'].' '.($counts['all'] === 1 ? 'documento' : 'documentos').' · '.(($counts['awaiting_signature'] ?? 0) + ($counts['partially_signed'] ?? 0)).' aguardando assinatura'">
        <x-admin.button :href="route('admin.gestao.templates.index')">Modelos</x-admin.button>
    </x-admin.page-header>

    @unless ($signatureConfigured)
        <div class="mb-4 rounded-xl border border-warning-border bg-warning-muted p-4 text-sm text-warning">
            Provedor de assinatura não configurado. Defina <code>CLICKSIGN_ACCESS_TOKEN</code> e <code>CLICKSIGN_WEBHOOK_SECRET</code> no <code>.env</code> (use o sandbox para testar). <a href="{{ route('admin.gestao.integrations.index') }}" class="font-semibold underline">Ver integrações</a>
        </div>
    @endunless

    <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Filtrar por status">
        @foreach (['all' => 'Todos'] + \App\Enums\DocumentStatus::options() as $key => $label)
            <a href="{{ route('admin.gestao.documents.index', array_filter(['status' => $key !== 'all' ? $key : null, 'type' => $type])) }}" role="tab" aria-selected="{{ $current === $key ? 'true' : 'false' }}"
                @class(['rounded-full border px-3 py-1 text-xs font-medium transition-colors', 'border-primary-border bg-primary-muted text-primary-hover' => $current === $key, 'border-border text-foreground-secondary hover:border-border-hover hover:bg-card-hover hover:text-foreground' => $current !== $key])>{{ $label }} <span class="tabular-nums opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    @if ($documents->isEmpty())
        <x-admin.card :padding="false"><x-admin.empty-state title="Nenhum documento" description="Gere documentos na aba Documentos de cada projeto." /></x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">Documento</th>
                    <th scope="col" class="admin-col-wide">Cliente / projeto</th>
                    <th scope="col" class="admin-col-wide">Assinaturas</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td data-label="Documento"><a class="font-semibold transition-colors hover:text-primary-hover" href="{{ route('admin.gestao.documents.show', $document) }}">{{ $document->type->label() }}</a><span class="block text-xs text-foreground-muted">v{{ $document->version }} · atualizado em {{ $document->updated_at->format('d/m/Y H:i') }}</span></td>
                        <td data-label="Cliente / projeto" class="admin-col-wide">{{ $document->client->displayName() }}<span class="block text-xs text-foreground-muted">{{ $document->project->name }}</span></td>
                        <td data-label="Assinaturas" class="admin-col-wide tabular-nums text-foreground-secondary">{{ $document->signers->where('status.value', 'signed')->count() }}/{{ $document->signers->count() }}</td>
                        <td data-label="Status"><x-admin.badge :tone="$document->status->tone()">● {{ $document->status->label() }}</x-admin.badge></td>
                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-admin.button size="sm" :href="route('admin.gestao.documents.show', $document)">Abrir</x-admin.button>
                                <x-admin.dropdown>
                                    <x-admin.menu-item :href="route('admin.gestao.documents.download', [$document, 'previa'])" target="_blank">Pré-visualizar PDF</x-admin.menu-item>
                                    @if ($document->signed_file_path)<x-admin.menu-item :href="route('admin.gestao.documents.download', [$document, 'assinado'])">Baixar PDF assinado</x-admin.menu-item>@endif
                                    <x-admin.menu-item :href="route('admin.gestao.projects.show', ['project' => $document->project, 'aba' => 'documentos'])">Abrir projeto</x-admin.menu-item>
                                </x-admin.dropdown>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$documents" />
    @endif
</x-layouts.admin>
