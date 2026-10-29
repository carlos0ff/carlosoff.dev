@php
    $groups = [
        ['title' => null, 'items' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ]],
        ['title' => 'Gestão de clientes', 'items' => [
            ['label' => 'Visão geral', 'route' => 'admin.gestao.dashboard', 'active' => 'admin.gestao.dashboard', 'icon' => 'M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z'],
            ['label' => 'Clientes', 'route' => 'admin.gestao.clients.index', 'active' => 'admin.gestao.clients.*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Projetos de clientes', 'route' => 'admin.gestao.projects.index', 'active' => 'admin.gestao.projects.*', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
            ['label' => 'Contratos', 'route' => 'admin.gestao.contracts.index', 'active' => 'admin.gestao.contracts.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['label' => 'Documentos e assinaturas', 'route' => 'admin.gestao.documents.index', 'active' => 'admin.gestao.documents.*', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
            ['label' => 'Modelos', 'route' => 'admin.gestao.templates.index', 'active' => 'admin.gestao.templates.*', 'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2'],
            ['label' => 'Cobranças', 'route' => 'admin.gestao.charges.index', 'active' => 'admin.gestao.charges.*', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Solicitações adicionais', 'route' => 'admin.gestao.scope.index', 'active' => 'admin.gestao.scope.*', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
            ['label' => 'Chamados', 'route' => 'admin.gestao.tickets.index', 'active' => 'admin.gestao.tickets.*', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
            ['label' => 'Monitoramento', 'route' => 'admin.gestao.monitoring.index', 'active' => 'admin.gestao.monitoring.*', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
            ['label' => 'Auditoria', 'route' => 'admin.gestao.audit.index', 'active' => 'admin.gestao.audit.*', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            ['label' => 'API e integrações', 'route' => 'admin.gestao.integrations.index', 'active' => 'admin.gestao.integrations.*', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
        ]],
        ['title' => 'Conteúdo', 'items' => [
            ['label' => 'Projetos', 'route' => 'admin.projects.index', 'active' => 'admin.projects.*', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
            ['label' => 'Experiência', 'route' => 'admin.experiences.index', 'active' => 'admin.experiences.*', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'Sobre', 'route' => 'admin.about.edit', 'active' => 'admin.about.*', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['label' => 'Stack', 'route' => 'admin.technologies.index', 'active' => 'admin.technologies.*', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
            ['label' => 'Cursos e Certificações', 'route' => 'admin.courses.index', 'active' => 'admin.courses.*', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ['label' => 'Formação Acadêmica', 'route' => 'admin.education.index', 'active' => 'admin.education.*', 'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
        ]],
        ['title' => 'Analytics', 'items' => [
            ['label' => 'Visão geral', 'route' => 'admin.analytics.overview', 'active' => 'admin.analytics.overview', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['label' => 'Páginas', 'route' => 'admin.analytics.pages', 'active' => 'admin.analytics.pages', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['label' => 'Projetos', 'route' => 'admin.analytics.projects', 'active' => 'admin.analytics.projects', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        ]],
        ['title' => 'Sistema', 'items' => [
            ['label' => 'Logs', 'route' => 'admin.logs.index', 'active' => 'admin.logs.*', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Usuários', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Configurações', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ]],
    ];
@endphp

{{-- Fundo escuro que fecha a sidebar no mobile --}}
<div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-overlay lg:hidden"></div>

<aside
    id="admin-sidebar"
    aria-label="Menu do painel"
    class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-[#0f172a] text-zinc-300 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
>
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-white/10 px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold text-white">
            <img src="{{ asset('animated/laravel-animated.svg') }}" alt="" class="h-6 w-7">
            <span>Painel</span>
        </a>
        <button type="button" data-sidebar-toggle aria-label="Fechar menu" class="grid h-9 w-9 place-items-center rounded-md border border-white/15 bg-white/5 text-zinc-400 transition-colors hover:bg-white/10 hover:text-white lg:hidden">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto no-scrollbar px-3 py-4">
        @foreach ($groups as $group)
            <div>
                @if ($group['title'])
                    <p class="mb-1 px-3 text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ $group['title'] }}</p>
                @endif

                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs($item['active']))
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($active) aria-current="page" @endif
                                @class([
                                    'flex items-center gap-3 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    'bg-white/10 text-white' => $active,
                                    'text-zinc-400 hover:bg-white/5 hover:text-white' => ! $active,
                                ])
                            >
                                <svg aria-hidden="true" @class(['h-[18px] w-[18px] shrink-0', 'text-blue-400' => $active]) fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 space-y-1 border-t border-white/10 p-3">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-md px-3 py-1.5 text-sm font-medium text-zinc-400 transition-colors hover:bg-white/5 hover:text-white">
            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Ver site
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-1.5 text-left text-sm font-medium text-zinc-400 transition-colors hover:bg-white/5 hover:text-white">
                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sair
            </button>
        </form>
    </div>
</aside>
