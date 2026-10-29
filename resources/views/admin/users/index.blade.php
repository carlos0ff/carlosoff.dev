<x-layouts.admin title="Usuários" :breadcrumbs="['Sistema' => null, 'Usuários' => null]">
    <x-admin.page-header title="Usuários" subtitle="Administradores com acesso ao painel." />

    <x-admin.table>
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">E-mail</th>
                <th scope="col">Criado em</th>
                <th scope="col" class="text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr data-user-row>
                    <td data-label="Nome" class="font-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<x-admin.badge tone="primary">Você</x-admin.badge>@endif</td>
                    <td data-label="E-mail">{{ $user->email }}</td>
                    <td data-label="Criado em" class="whitespace-nowrap text-foreground-secondary">{{ $user->created_at?->format('d/m/Y') }}</td>
                    <td data-label="">
                        <div class="flex justify-end">
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="{{ $user->email }} perderá o acesso ao painel. Esta ação não poderá ser desfeita." data-confirm-title="Excluir usuário?">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.button type="submit" size="sm" variant="danger">Excluir</x-admin.button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>

    <x-admin.card title="Novo administrador" class="mt-6">
        <form method="POST" action="{{ route('admin.users.store') }}" novalidate data-user-form>
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="name" label="Nome" required />
                <x-admin.input name="email" label="E-mail" type="email" required />
                <x-admin.input name="password" label="Senha" type="password" required autocomplete="new-password" help="Mínimo de 8 caracteres." />
                <x-admin.input name="password_confirmation" label="Confirmar senha" type="password" required autocomplete="new-password" />
            </div>
            <div class="mt-5">
                <x-admin.button type="submit" variant="primary">Criar administrador</x-admin.button>
            </div>
        </form>
    </x-admin.card>
</x-layouts.admin>
