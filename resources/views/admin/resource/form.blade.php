@php
    $plural = $labels['plural'];
    $isEdit = $model->exists;
    $action = $isEdit ? route($routePrefix.'.update', $model->id) : route($routePrefix.'.store');
    $title = $isEdit ? 'Editar '.$labels['singular'] : 'Adicionar '.$labels['singular'];
@endphp

<x-layouts.admin :title="$title" :breadcrumbs="[$plural => route($routePrefix.'.index'), ($isEdit ? 'Editar' : 'Novo') => null]">
    <x-admin.page-header :title="$title" />

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate data-resource-form>
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-admin.card>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($fields as $field)
                    <x-admin.form-field :field="$field" :model="$model" />
                @endforeach
            </div>
        </x-admin.card>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-admin.button type="submit" variant="primary">{{ $isEdit ? 'Salvar alterações' : 'Criar' }}</x-admin.button>
            <x-admin.button :href="route($routePrefix.'.index')" variant="ghost">Cancelar</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
