<x-layouts.admin title="Configurações" :breadcrumbs="['Sistema' => null, 'Configurações' => null]">
    <x-admin.page-header title="Configurações" subtitle="Ajustes gerais do site público." />

    <form method="POST" action="{{ route('admin.settings.update') }}" novalidate data-settings-form class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.card title="Projetos">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="projects_per_page" label="Projetos por página" type="number" min="1" max="24" :value="$settings['projects_per_page']" required help="Quantidade de cards em /projetos antes da paginação." />
                <x-admin.input name="featured_projects_limit" label="Limite de destaques" type="number" min="0" max="20" :value="$settings['featured_projects_limit']" required help="Máximo de projetos em destaque ao mesmo tempo. 0 = sem limite." />
            </div>
        </x-admin.card>

        <x-admin.card title="Analytics">
            <x-admin.toggle name="track_page_views" label="Registrar acessos às páginas públicas" :checked="$settings['track_page_views']" help="O IP não é salvo (apenas um hash diário) e bots conhecidos são ignorados. Desligar interrompe a contagem, sem apagar o histórico." />
        </x-admin.card>

        <div class="flex flex-wrap items-center gap-3">
            <x-admin.button type="submit" variant="primary">Salvar configurações</x-admin.button>
        </div>
    </form>

    <x-admin.card title="Cache do conteúdo público" class="mt-6">
        <p class="text-sm text-foreground-secondary">O site guarda em cache o conteúdo vindo do banco e o limpa sozinho a cada alteração feita no painel. Use o botão se editou o banco por fora do painel.</p>

        <form method="POST" action="{{ route('admin.settings.clear-cache') }}" class="mt-4">
            @csrf
            <x-admin.button type="submit">Limpar cache</x-admin.button>
        </form>
    </x-admin.card>
</x-layouts.admin>
