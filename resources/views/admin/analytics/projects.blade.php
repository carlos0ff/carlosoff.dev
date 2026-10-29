<x-layouts.admin title="Projetos mais acessados" :breadcrumbs="['Analytics' => route('admin.analytics.overview'), 'Projetos' => null]">
    <x-admin.page-header title="Projetos mais acessados" subtitle="Visualizações das páginas individuais dos projetos.">
        <x-admin.range-tabs :options="\App\Services\Analytics::RANGES" :current="$range" />
    </x-admin.page-header>

    <x-admin.card>
        <x-admin.bar-list
            unit="visualizações"
            empty="Nenhum projeto foi acessado no período."
            :items="$projects->map(fn ($row) => ['label' => $row->title, 'value' => (int) $row->views, 'href' => url('/projetos/'.$row->slug)])"
        />
    </x-admin.card>
</x-layouts.admin>
