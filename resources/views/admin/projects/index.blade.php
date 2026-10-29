@php
    $tabs = ['all' => 'Todos', 'published' => 'Publicados', 'draft' => 'Rascunhos', 'featured' => '★ Destaques', 'trashed' => 'Lixeira'];
    $keep = ['sort' => request('sort'), 'direction' => request('direction')];
    $sortLink = function (string $column, string $label) use ($filters) {
        $next = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';
        $arrow = $filters['sort'] === $column ? ($filters['direction'] === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a href="'.e(request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => null])).'" class="transition-colors hover:text-foreground">'.e($label).$arrow.'</a>';
    };
@endphp

<x-layouts.admin title="Projetos" :breadcrumbs="['Projetos' => null]">
    <x-admin.page-header title="Projetos" :subtitle="$counts['all'].' '.($counts['all'] === 1 ? 'projeto' : 'projetos').($featuredLimit > 0 ? ' · '.$counts['featured'].' de '.$featuredLimit.' destaques' : '')">
        <x-admin.button variant="primary" :href="route('admin.projects.create')">+ Novo projeto</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.projects.index')" :search="$filters['search']" :tabs="$tabs" :current="$filters['status']" :counts="$counts" :keep="$keep" placeholder="Pesquisar projeto..." />

    @if ($projects->isEmpty())
        <x-admin.card :padding="false">
            <x-admin.empty-state
                :title="$filters['search'] !== '' || $filters['status'] !== 'all' ? 'Nenhum projeto encontrado' : 'Nenhum projeto cadastrado'"
                :description="$filters['search'] !== '' || $filters['status'] !== 'all' ? 'Ajuste a busca ou o filtro.' : 'Crie o primeiro projeto para ele aparecer no site.'"
            />
        </x-admin.card>
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th scope="col">{!! $sortLink('title', 'Projeto') !!}</th>
                    <th scope="col" class="admin-col-wide">Slug</th>
                    <th scope="col" class="admin-col-wide">Tecnologias</th>
                    <th scope="col">Status</th>
                    <th scope="col">Destaque</th>
                    <th scope="col" class="text-right">Visualizações</th>
                    <th scope="col" class="admin-col-wide">{!! $sortLink('updated_at', 'Atualizado em') !!}</th>
                    <th scope="col" class="text-right">Ações</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($projects as $project)
                    <tr data-project-row>
                        <td data-label="Projeto">
                            <span class="flex items-center gap-3">
                                <span class="admin-col-wide hidden h-9 w-14 shrink-0 overflow-hidden rounded-md border border-border bg-card-hover md:block">
                                    @if ($project->image_url)
                                        <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover object-top">
                                    @endif
                                </span>
                                <span class="font-semibold">{{ $project->title }}</span>
                            </span>
                        </td>
                        <td data-label="Slug" class="admin-col-wide"><code class="text-xs text-foreground-secondary">/{{ $project->slug }}</code></td>
                        <td data-label="Tecnologias" class="admin-col-wide max-w-[10rem]"><span class="line-clamp-2 text-xs text-foreground-secondary">{{ implode(', ', $project->technologies ?? []) ?: '—' }}</span></td>
                        <td data-label="Status">
                            @if ($project->trashed())
                                <x-admin.badge tone="danger">Excluído</x-admin.badge>
                            @elseif ($project->is_active)
                                <x-admin.badge tone="success">● Publicado</x-admin.badge>
                            @else
                                <x-admin.badge tone="warning">● Rascunho</x-admin.badge>
                            @endif
                        </td>
                        <td data-label="Destaque">
                            @if ($project->trashed())
                                <span class="text-foreground-muted">—</span>
                            @else
                                <form method="POST" action="{{ route('admin.projects.feature', $project) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-admin.icon-button :label="$project->featured ? 'Remover destaque' : 'Colocar em destaque'" :active="$project->featured">{{ $project->featured ? '★' : '☆' }}</x-admin.icon-button>
                                </form>
                            @endif
                        </td>
                        <td data-label="Visualizações" class="text-right tabular-nums">{{ number_format($views[$project->id] ?? 0, 0, ',', '.') }}</td>
                        <td data-label="Atualizado em" class="admin-col-wide whitespace-nowrap text-foreground-secondary">{{ $project->updated_at->format('d/m/Y') }}</td>

                        <td data-label="">
                            <div class="flex items-center justify-end gap-1.5">
                                @if ($project->trashed())
                                    <form method="POST" action="{{ route('admin.projects.restore', $project->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-admin.button type="submit" size="sm">Restaurar</x-admin.button>
                                    </form>
                                @else
                                    <x-admin.button size="sm" :href="route('admin.projects.edit', $project)">Editar</x-admin.button>

                                    <x-admin.dropdown>
                                        <x-admin.menu-item :href="route('admin.projects.preview', $project)">Visualizar</x-admin.menu-item>

                                        <form method="POST" action="{{ route('admin.projects.toggle', $project) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin.menu-item>{{ $project->is_active ? 'Despublicar' : 'Publicar' }}</x-admin.menu-item>
                                        </form>

                                        @if ($canReorder)
                                            @foreach (['up' => 'Mover para cima ↑', 'down' => 'Mover para baixo ↓'] as $direction => $label)
                                                <form method="POST" action="{{ route('admin.projects.move', $project) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                                    <x-admin.menu-item>{{ $label }}</x-admin.menu-item>
                                                </form>
                                            @endforeach
                                        @endif

                                        <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" data-confirm="Ele será movido para a lixeira e poderá ser restaurado depois." data-confirm-title="Tem certeza que deseja excluir este projeto?" data-confirm-button="Excluir">
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

        <x-admin.pagination :paginator="$projects" />
    @endif
</x-layouts.admin>
