@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $rangeLabel = \App\Services\Analytics::RANGES[$range];
    $pageItems = $topPages->map(fn ($row) => ['label' => $row->path, 'value' => (int) $row->views, 'href' => url($row->path)]);
    $projectItems = $topProjects->map(fn ($row) => ['label' => $row->title, 'value' => (int) $row->views, 'href' => route('admin.analytics.projects')]);
    $mapBreakdown = fn ($rows) => $rows->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->views]);
@endphp

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name ?? 'José Carlos')->trim()->explode(' ')->first();
@endphp

<x-layouts.admin title="Dashboard">
    {{-- Saudação --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-incognito text-2xl font-bold tracking-tight sm:text-3xl">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-foreground-secondary first-letter:uppercase">{{ now()->translatedFormat('l, d \d\e F') }} · visão geral do portfólio</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.range-tabs :options="\App\Services\Analytics::RANGES" :current="$range" />
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-hover">Ver site <span aria-hidden="true">↗</span></a>
        </div>
    </div>

    {{-- Cards principais --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Projetos" icon="folder" accent="blue" :value="$fmt($counts['projects']['total'])" :hint="$counts['projects']['published'].' publicados'" :href="route('admin.projects.index')" />
        <x-admin.stat label="Experiências" icon="briefcase" accent="purple" :value="$fmt($counts['experiences']['total'])" :hint="$counts['experiences']['published'].' publicadas'" :href="route('admin.experiences.index')" />
        <x-admin.stat label="Tecnologias" icon="cpu" accent="cyan" :value="$fmt($counts['technologies']['total'])" :hint="$counts['technologies']['published'].' publicadas'" :href="route('admin.technologies.index')" />
        <x-admin.stat label="Visitas" icon="eye" accent="green" :value="$fmt($summary['total'])" hint="total de acessos" :href="route('admin.analytics.overview')" />
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Acessos hoje" icon="calendar" accent="amber" :value="$fmt($summary['today'])" :hint="$summary['unique_today'].' visitantes únicos'" />
        <x-admin.stat label="Últimos 7 dias" icon="clock" accent="blue" :value="$fmt($summary['week'])" />
        <x-admin.stat label="Últimos 30 dias" icon="clock" accent="primary" :value="$fmt($summary['month'])" />
        <x-admin.stat label="Cursos / Formação" icon="graduation" accent="purple" :value="$counts['courses']['total'].' / '.$counts['education']['total']" hint="cursos e certificações / formação" />
    </div>

    {{-- Gráfico --}}
    <x-admin.card title="Acessos" class="mt-6">
        <x-slot:actions>
            <x-admin.range-tabs :options="\App\Services\Analytics::GRANULARITIES" :current="$granularity" param="granularity" label="Agrupar por" />
        </x-slot:actions>

        @if ($series['total'] > 0)
            <x-admin.line-chart :labels="$series['labels']" :values="$series['values']" />
            <p class="mt-2 text-xs text-foreground-muted">{{ $fmt($series['total']) }} acessos · período: {{ $rangeLabel }}</p>
        @else
            <x-admin.empty-state title="Ainda não há acessos neste período" description="Os acessos às páginas públicas passam a ser contabilizados automaticamente." />
        @endif
    </x-admin.card>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Páginas mais acessadas">
            <x-slot:actions><a href="{{ route('admin.analytics.pages', ['range' => $range]) }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Ver todas</a></x-slot:actions>
            <x-admin.bar-list :items="$pageItems" />
        </x-admin.card>

        <x-admin.card title="Projetos mais acessados">
            <x-slot:actions><a href="{{ route('admin.analytics.projects', ['range' => $range]) }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Ver todos</a></x-slot:actions>
            <x-admin.bar-list :items="$projectItems" unit="visualizações" empty="Nenhum projeto foi acessado no período." />
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-3">
        <x-admin.card title="Dispositivos"><x-admin.bar-list :items="$mapBreakdown($devices)" /></x-admin.card>
        <x-admin.card title="Navegadores"><x-admin.bar-list :items="$mapBreakdown($browsers)" /></x-admin.card>
        <x-admin.card title="Origem dos acessos"><x-admin.bar-list :items="$mapBreakdown($referers)" /></x-admin.card>
    </div>

    <x-admin.card title="Atividade recente" class="mt-6" :padding="false">
        <x-slot:actions><a href="{{ route('admin.logs.index') }}" class="text-xs font-semibold text-primary-hover transition-colors hover:text-primary hover:underline">Ver logs</a></x-slot:actions>

        @if ($recentLogs->isEmpty())
            <x-admin.empty-state title="Nenhuma alteração registrada ainda" />
        @else
            <ul class="divide-y divide-border-subtle">
                @foreach ($recentLogs as $log)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                        <span><strong>{{ $log->user_name }}</strong> {{ mb_strtolower($log->action->label()) }} <span class="text-foreground-secondary">{{ mb_strtolower($log->resource_type) }}</span> <strong>{{ $log->resource_label }}</strong></span>
                        <time class="text-xs text-foreground-muted" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</x-layouts.admin>
