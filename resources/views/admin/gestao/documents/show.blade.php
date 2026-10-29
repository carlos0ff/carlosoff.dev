<x-layouts.admin :title="$document->title" :breadcrumbs="['Gestão' => route('admin.gestao.dashboard'), 'Documentos' => route('admin.gestao.documents.index'), $document->type->label() => null]">
    <x-admin.page-header :title="$document->title" :subtitle="'Versão '.$document->version.' · '.$document->uuid">
        <x-admin.status :value="$document->status" />
        <x-admin.button :href="route('admin.gestao.documents.download', [$document, 'previa'])" target="_blank">Pré-visualizar PDF</x-admin.button>
    </x-admin.page-header>

    {{-- Etapas do fluxo --}}
    @php($steps = [
        'Rascunho' => true,
        'Revisão' => $document->status->value !== 'draft',
        'Versão imutável (hash)' => $document->isLocked(),
        'Enviado ao provedor' => (bool) $document->provider_envelope_id,
        'Assinado' => $document->status->value === 'signed',
        'PDF assinado arquivado' => (bool) $document->signed_file_path,
    ])
    <ol class="mb-6 flex flex-wrap gap-2 text-xs" data-document-steps>
        @foreach ($steps as $label => $done)
            <li @class(['rounded-full border px-3 py-1', 'border-success-border bg-success-muted text-success' => $done, 'border-border text-foreground-muted' => ! $done])>{{ $done ? '✓' : '○' }} {{ $label }}</li>
        @endforeach
    </ol>

    @if ($document->provider_error)
        <div class="mb-4 rounded-xl border border-error-border bg-error-muted p-4 text-sm text-primary-hover">Erro no provedor: {{ $document->provider_error }}</div>
    @endif
    @if ($document->template && ! $document->template->isLegallyReviewed())
        <div class="mb-4 rounded-xl border border-warning-border bg-warning-muted p-4 text-sm text-warning">O modelo "{{ $document->template->name }}" ainda não passou por revisão jurídica: o documento só pode ser usado como minuta.</div>
    @endif
    @if ($missing > 0)
        <div class="mb-4 rounded-xl border border-warning-border bg-warning-muted p-4 text-sm text-warning">{{ $missing }} campo(s) sem valor destacado(s) no texto. Complete os cadastros e gere uma nova versão, ou edite o texto.</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Documento" :padding="false">
                <x-slot:actions><a href="{{ route('admin.gestao.documents.download', [$document, 'previa']) }}" target="_blank" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Abrir PDF ↗</a></x-slot:actions>
                <div class="max-h-[44rem] overflow-y-auto bg-white px-8 py-7 text-[13px] leading-relaxed text-neutral-900 [&_h1]:mb-2 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:mb-1.5 [&_h2]:mt-5 [&_h2]:font-bold [&_mark]:rounded [&_mark]:bg-amber-200 [&_mark]:px-1 [&_p]:mb-2 [&_p]:text-justify [&_table]:my-3 [&_table]:w-full [&_td]:border [&_td]:border-neutral-300 [&_td]:px-2 [&_td]:py-1 [&_th]:border [&_th]:border-neutral-300 [&_th]:bg-neutral-100 [&_th]:px-2 [&_th]:py-1 [&_th]:text-left" data-document-preview>
                    {!! $document->body_html !!}
                </div>
                @if ($document->isEditable())
                    <details class="border-t border-border-subtle px-5 py-4" @if ($errors->has('body_html')) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-primary-hover">Editar texto (HTML)</summary>
                        <form method="POST" action="{{ route('admin.gestao.documents.update', $document) }}" class="mt-4 space-y-3">
                            @csrf @method('PUT')
                            <x-admin.textarea name="body_html" label="HTML do documento" :value="$document->body_html" :rows="22" class="font-mono" help="Campos destacados em amarelo estão sem valor. Scripts e eventos são removidos ao salvar." />
                            <x-admin.button type="submit" size="sm" variant="primary">Salvar texto</x-admin.button>
                        </form>
                    </details>
                @endif
            </x-admin.card>

            <x-admin.card title="Eventos do provedor e evidências">
                <ul class="space-y-2 text-xs">
                    @forelse ($document->events as $event)
                        <li><span class="text-foreground-muted">{{ $event->created_at->format('d/m/Y H:i:s') }}</span> · <strong>{{ $event->event }}</strong> · {{ $event->signature_valid ? 'HMAC válido' : 'HMAC INVÁLIDO (ignorado)' }} {{ $event->processed_at ? '· processado' : '' }}</li>
                    @empty
                        <li class="text-foreground-muted">Nenhum evento recebido.</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Integridade">
                <x-admin.kv class="!grid-cols-1" :items="[
                    'Projeto' => '<a class=\'hover:underline\' href=\''.route('admin.gestao.projects.show', $document->project).'\'>'.e($document->project->name).'</a>',
                    'Modelo' => $document->template ? e($document->template->name).' v'.$document->template_version : null,
                    'Congelado em' => $document->locked_at?->format('d/m/Y H:i'),
                    'SHA-256 do PDF enviado' => $document->file_sha256 ? '<code class=\'text-xs break-all\'>'.$document->file_sha256.'</code>' : null,
                    'Provedor' => e($document->provider),
                    'Envelope' => e($document->provider_envelope_id),
                    'Enviado em' => $document->sent_at?->format('d/m/Y H:i'),
                    'Concluído em' => $document->completed_at?->format('d/m/Y H:i'),
                    'SHA-256 do PDF assinado' => $document->signed_file_sha256 ? '<code class=\'text-xs break-all\'>'.$document->signed_file_sha256.'</code>' : null,
                ]" />
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($document->file_path)<x-admin.button size="sm" :href="route('admin.gestao.documents.download', [$document, 'original'])">PDF enviado</x-admin.button>@endif
                    @if ($document->signed_file_path)<x-admin.button size="sm" variant="primary" :href="route('admin.gestao.documents.download', [$document, 'assinado'])">PDF assinado</x-admin.button>@endif
                </div>
            </x-admin.card>

            <x-admin.card title="Signatários">
                <ul class="mb-4 space-y-3 text-sm">
                    @forelse ($document->signers as $signer)
                        <li class="flex items-start justify-between gap-2">
                            <span><strong>{{ $signer->name }}</strong> <x-admin.status :value="$signer->status" /><br><span class="text-foreground-muted">{{ $signer->email }} · {{ $signer->role->label() }} · autenticação: {{ $signer->auth_method }}</span>
                                @if ($signer->signed_at)<br><span class="text-xs text-success">Assinou em {{ $signer->signed_at->format('d/m/Y H:i') }}</span>@endif</span>
                            @if ($document->isEditable())
                                <form method="POST" action="{{ route('admin.gestao.documents.signers.destroy', [$document, $signer]) }}">@csrf @method('DELETE')<x-admin.icon-button label="Remover" danger>✕</x-admin.icon-button></form>
                            @endif
                        </li>
                    @empty
                        <li class="text-foreground-muted">Nenhum signatário.</li>
                    @endforelse
                </ul>
                @if ($document->isEditable())
                    <form method="POST" action="{{ route('admin.gestao.documents.signers.store', $document) }}" class="space-y-3 border-t border-border-subtle pt-4">
                        @csrf
                        <x-admin.input name="name" label="Nome" required />
                        <x-admin.input name="email" label="E-mail" type="email" required />
                        <x-admin.input name="document_number" label="CPF" />
                        <x-admin.select name="role" label="Papel" :options="\App\Enums\SignerRole::options()" />
                        <x-admin.select name="auth_method" label="Autenticação" :options="['email' => 'Token por e-mail', 'sms' => 'Token por SMS', 'whatsapp' => 'Token por WhatsApp', 'icp_brasil' => 'Certificado ICP-Brasil']" />
                        <x-admin.input name="sign_order" label="Ordem" type="number" value="1" />
                        <x-admin.button type="submit" size="sm">Adicionar signatário</x-admin.button>
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card title="Ações">
                <div class="space-y-3">
                    @if ($document->status->value === 'draft')
                        <form method="POST" action="{{ route('admin.gestao.documents.review', $document) }}">@csrf<x-admin.button type="submit" size="sm">Enviar para revisão</x-admin.button></form>
                    @endif
                    @can('gestao.admin')
                        @if ($document->status->value === 'under_review' && ! $document->isLocked())
                            <form method="POST" action="{{ route('admin.gestao.documents.lock', $document) }}" data-confirm="O PDF será gerado e o hash registrado. O texto não poderá mais ser editado." data-confirm-title="Congelar versão?" data-confirm-button="Congelar">@csrf<x-admin.button type="submit" size="sm" variant="primary">Aprovar e congelar versão</x-admin.button></form>
                        @endif
                        @if ($document->isLocked() && ! $document->provider_envelope_id && ! $document->isFinal())
                            <form method="POST" action="{{ route('admin.gestao.documents.send', $document) }}">@csrf<x-admin.button type="submit" size="sm" variant="primary" :disabled="! $signatureConfigured">Enviar para assinatura</x-admin.button></form>
                        @endif
                    @endcan
                    @if ($document->provider_envelope_id && ! $document->signed_file_path)
                        <form method="POST" action="{{ route('admin.gestao.documents.sync', $document) }}">@csrf<x-admin.button type="submit" size="sm">Sincronizar com o provedor</x-admin.button></form>
                    @endif
                    @can('gestao.admin')
                        @unless ($document->isFinal())
                            <form method="POST" action="{{ route('admin.gestao.documents.cancel', $document) }}" class="flex gap-2">@csrf<input name="reason" required placeholder="Motivo" class="min-w-0 flex-1 rounded-md border border-border bg-background px-3 py-1.5 text-sm"><x-admin.button type="submit" size="sm" variant="danger">Cancelar</x-admin.button></form>
                        @endunless
                    @endcan
                </div>
                <p class="mt-4 text-xs text-foreground-muted">A validade jurídica depende do método de assinatura, da identificação dos signatários e das circunstâncias do documento. O sistema não simula assinaturas.</p>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
