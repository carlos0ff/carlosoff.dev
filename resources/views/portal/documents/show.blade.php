<x-layouts.portal :title="$document->title">
    <x-admin.page-header :title="$document->title" :subtitle="$document->project->name.' · versão '.$document->version"><x-admin.status :value="$document->status" /></x-admin.page-header>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Assinaturas" class="lg:col-span-2">
            <ul class="space-y-2 text-sm">
                @foreach ($document->signers as $signer)
                    <li class="flex flex-wrap justify-between gap-2"><span>{{ $signer->name }} <span class="text-foreground-muted">({{ $signer->role->label() }})</span></span><span><x-admin.status :value="$signer->status" /> {{ $signer->signed_at?->format('d/m/Y H:i') }}</span></li>
                @endforeach
            </ul>
            @if (in_array($document->status->value, ['awaiting_signature', 'partially_signed'], true))
                <p class="mt-4 text-sm text-foreground-secondary">O link de assinatura foi enviado pelo provedor de assinatura eletrônica para o e-mail de cada signatário. Confira também a caixa de spam.</p>
                @foreach ($document->signers->whereNotNull('sign_url') as $signer)
                    @if ($signer->email === auth()->user()->email)<x-admin.button class="mt-3" variant="primary" :href="$signer->sign_url" target="_blank" rel="noopener">Assinar agora</x-admin.button>@endif
                @endforeach
            @endif
        </x-admin.card>
        <x-admin.card title="Arquivos">
            <div class="flex flex-col gap-2">
                @if ($document->file_path)<x-admin.button size="sm" :href="route('portal.documents.download', [$document, 'original'])">PDF enviado para assinatura</x-admin.button>@endif
                @if ($document->signed_file_path)<x-admin.button size="sm" variant="primary" :href="route('portal.documents.download', [$document, 'assinado'])">PDF assinado</x-admin.button>@endif
            </div>
            @if ($document->file_sha256)<p class="mt-3 break-all text-xs text-foreground-muted">SHA-256: {{ $document->file_sha256 }}</p>@endif
        </x-admin.card>
    </div>
</x-layouts.portal>
