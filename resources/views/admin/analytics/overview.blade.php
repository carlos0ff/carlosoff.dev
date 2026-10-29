@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $map = fn ($rows) => $rows->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->views]);
@endphp

<x-layouts.admin title="Analytics" :breadcrumbs="['Analytics' => null, 'Visão geral' => null]">
    <x-admin.page-header title="Analytics" subtitle="Acessos reais às páginas públicas (sem dados pessoais: o IP nunca é salvo).">
        <x-admin.range-tabs :options="\App\Services\Analytics::RANGES" :current="$range" />
    </x-admin.page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Hoje" :value="$fmt($summary['today'])" :hint="$summary['unique_today'].' visitantes únicos'" />
        <x-admin.stat label="7 dias" :value="$fmt($summary['week'])" />
        <x-admin.stat label="30 dias" :value="$fmt($summary['month'])" />
        <x-admin.stat label="Total" :value="$fmt($summary['total'])" />
    </div>

    <x-admin.card title="Acessos" class="mt-6">
        <x-slot:actions>
            <x-admin.range-tabs :options="\App\Services\Analytics::GRANULARITIES" :current="$granularity" param="granularity" label="Agrupar por" />
        </x-slot:actions>

        @if ($series['total'] > 0)
            <x-admin.line-chart :labels="$series['labels']" :values="$series['values']" />
        @else
            <x-admin.empty-state title="Ainda não há acessos neste período" />
        @endif
    </x-admin.card>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <x-admin.card title="Dispositivos"><x-admin.bar-list :items="$map($devices)" /></x-admin.card>
        <x-admin.card title="Navegadores"><x-admin.bar-list :items="$map($browsers)" /></x-admin.card>
        <x-admin.card title="Sistemas operacionais"><x-admin.bar-list :items="$map($systems)" /></x-admin.card>
        <x-admin.card title="Origem dos acessos"><x-admin.bar-list :items="$map($referers)" /></x-admin.card>
    </div>
</x-layouts.admin>
