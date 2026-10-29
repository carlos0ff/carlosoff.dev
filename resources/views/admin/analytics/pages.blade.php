<x-layouts.admin title="Páginas mais acessadas" :breadcrumbs="['Analytics' => route('admin.analytics.overview'), 'Páginas' => null]">
    <x-admin.page-header title="Páginas mais acessadas" :subtitle="number_format($total, 0, ',', '.').' acessos no período'">
        <x-admin.range-tabs :options="\App\Services\Analytics::RANGES" :current="$range" />
    </x-admin.page-header>

    <x-admin.card>
        <x-admin.bar-list :items="$pages->map(fn ($row) => ['label' => $row->path, 'value' => (int) $row->views, 'href' => url($row->path)])" />
    </x-admin.card>
</x-layouts.admin>
