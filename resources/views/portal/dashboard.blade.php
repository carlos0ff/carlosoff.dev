@php($money = fn ($cents) => \App\Support\Money::format($cents))
<x-layouts.portal title="Início">
    <x-admin.page-header :title="'Olá, '.auth()->user()->name" subtitle="Resumo dos seus projetos e pendências." />

    @if ($pendingValidations->isNotEmpty() || $pendingDocuments->isNotEmpty())
        <x-admin.card title="Aguardando você" class="mb-6 border-warning-border">
            <ul class="space-y-2 text-sm">
                @foreach ($pendingValidations as $validation)
                    <li>Validar versão <strong>{{ $validation->version }}</strong> de <a class="text-primary-hover hover:underline" href="{{ route('portal.projects.show', $validation->project) }}#validacoes">{{ $validation->project->name }}</a></li>
                @endforeach
                @foreach ($pendingDocuments as $document)
                    <li>Assinar <a class="text-primary-hover hover:underline" href="{{ route('portal.documents.show', $document) }}">{{ $document->title }}</a></li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($projects as $project)
                <a href="{{ route('portal.projects.show', $project) }}" class="block rounded-xl border border-border bg-card p-5 transition-colors hover:border-border-hover hover:bg-card-hover">
                    <p class="font-semibold">{{ $project->name }}</p>
                    <div class="mt-2 flex flex-wrap gap-2"><x-admin.status :value="$project->development_status" /><x-admin.status :value="$project->operational_status" /><x-admin.status :value="$project->financial_status" /></div>
                </a>
            @empty
                <x-admin.card><p class="text-sm text-foreground-muted">Nenhum projeto vinculado.</p></x-admin.card>
            @endforelse
        </div>
        <div class="space-y-6">
            <x-admin.card title="Cobranças em aberto">
                @forelse ($openCharges as $charge)
                    <p class="flex justify-between gap-2 py-1 text-sm"><span>{{ $charge->due_date->format('d/m/Y') }} — {{ $charge->description }}</span><span class="tabular-nums">{{ $money($charge->outstandingCents()) }}</span></p>
                @empty
                    <p class="text-sm text-foreground-muted">Nenhuma cobrança em aberto.</p>
                @endforelse
            </x-admin.card>
            <x-admin.card title="Notificações recentes">
                @forelse ($notifications as $notification)
                    <p class="py-1 text-sm">{{ $notification->data['subject'] ?? '' }} <span class="text-xs text-foreground-muted">{{ $notification->created_at->format('d/m H:i') }}</span></p>
                @empty
                    <p class="text-sm text-foreground-muted">Nada por aqui.</p>
                @endforelse
            </x-admin.card>
        </div>
    </div>
</x-layouts.portal>
