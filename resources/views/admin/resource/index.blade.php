@php
    $plural = $labels['plural'];
    $tabs = ['all' => 'Todos', 'published' => 'Publicados', 'draft' => 'Rascunhos', 'trashed' => 'Lixeira'];
    $keep = ['sort' => request('sort'), 'direction' => request('direction')];
    $isTrash = $filters['status'] === 'trashed';
@endphp

<x-layouts.admin :title="$plural" :breadcrumbs="[$plural => null]">
    <x-admin.page-header :title="$plural" :subtitle="$counts['all'].' '.($counts['all'] === 1 ? 'item' : 'itens')">
        <x-admin.button variant="primary" :href="route($routePrefix.'.create')">+ Novo</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters :action="route($routePrefix.'.index')" :search="$filters['search']" :tabs="$tabs" :current="$filters['status']" :counts="$counts" :keep="$keep" placeholder="Pesquisar..." />

    @if ($items->isEmpty())
        <x-admin.card :padding="false">
            <x-admin.empty-state
                :title="$filters['search'] !== '' || $filters['status'] !== 'all' ? 'Nada encontrado' : 'Nada cadastrado ainda'"
                :description="$filters['search'] !== '' || $filters['status'] !== 'all' ? 'Ajuste a busca ou o filtro.' : 'Crie o primeiro item para ele aparecer no site.'"
            >
                @if ($filters['status'] === 'all' && $filters['search'] === '')
                    <x-admin.button variant="primary" :href="route($routePrefix.'.create')">+ Novo</x-admin.button>
                @endif
            </x-admin.empty-state>
        </x-admin.card>
    @else
        <x-admin.table data-resource-table>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col">
                            @if (! empty($column['sort']))
                                @php($nextDirection = $filters['sort'] === $column['sort'] && $filters['direction'] === 'asc' ? 'desc' : 'asc')
                                <a href="{{ request()->fullUrlWithQuery(['sort' => $column['sort'], 'direction' => $nextDirection, 'page' => null]) }}" class="transition-colors hover:text-foreground">
                                    {{ $column['label'] }}@if ($filters['sort'] === $column['sort']) <span aria-hidden="true">{{ $filters['direction'] === 'asc' ? '↑' : '↓' }}</span>@endif
                                </a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($items as $item)
                    <tr data-resource-row>
                        @foreach ($columns as $column)
                            @php($value = $column['render']($item))
                            <td data-label="{{ ($column['type'] ?? 'text') === 'image' ? '' : $column['label'] }}">
                                @if (($column['type'] ?? 'text') === 'image')
                                    <span class="grid h-10 w-10 place-items-center overflow-hidden rounded-md border border-border bg-white">
                                        @if ($value)
                                            <img src="{{ asset($value) }}" alt="" loading="lazy" class="h-full w-full object-contain">
                                        @else
                                            <span class="text-xs text-foreground-muted" aria-hidden="true">—</span>
                                        @endif
                                    </span>
                                @elseif (! empty($column['primary']))
                                    <span class="font-semibold">{{ $value }}</span>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach

                        <td data-label="Status">
                            @if ($item->trashed())
                                <x-admin.badge tone="danger">Excluído</x-admin.badge>
                            @elseif ($item->is_published)
                                <x-admin.badge tone="success">● Publicado</x-admin.badge>
                            @else
                                <x-admin.badge tone="warning">● Rascunho</x-admin.badge>
                            @endif
                        </td>

                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                @if ($item->trashed())
                                    <form method="POST" action="{{ route($routePrefix.'.restore', $item->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-admin.button type="submit" size="sm">Restaurar</x-admin.button>
                                    </form>
                                @else
                                    <x-admin.button size="sm" :href="route($routePrefix.'.edit', $item->id)">Editar</x-admin.button>

                                    <x-admin.dropdown>
                                        <form method="POST" action="{{ route($routePrefix.'.toggle', $item->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin.menu-item>{{ $item->is_published ? 'Despublicar' : 'Publicar' }}</x-admin.menu-item>
                                        </form>

                                        @if ($canReorder)
                                            @foreach (['up' => 'Mover para cima ↑', 'down' => 'Mover para baixo ↓'] as $direction => $label)
                                                <form method="POST" action="{{ route($routePrefix.'.move', $item->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                                    <x-admin.menu-item :disabled="($direction === 'up' && $loop->parent->first && $items->onFirstPage()) || ($direction === 'down' && $loop->parent->last && ! $items->hasMorePages())">{{ $label }}</x-admin.menu-item>
                                                </form>
                                            @endforeach
                                        @endif

                                        <form method="POST" action="{{ route($routePrefix.'.destroy', $item->id) }}" data-confirm="Ele será movido para a lixeira e poderá ser restaurado depois." data-confirm-title="Excluir item?" data-confirm-button="Excluir">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.menu-item :danger="true">Excluir</x-admin.menu-item>
                                        </form>
                                    </x-admin.dropdown>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>

        <x-admin.pagination :paginator="$items" />
    @endif
</x-layouts.admin>
