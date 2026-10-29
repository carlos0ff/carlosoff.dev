<x-layouts.admin-auth title="Portal do cliente">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-2xl border border-border bg-card p-8 shadow-lg shadow-black/30">
            <h1 class="font-incognito text-2xl font-bold tracking-tight">Portal do cliente</h1>
            <p class="mt-2 text-sm text-foreground-secondary">Acompanhe seus projetos, contratos, cobranças e chamados.</p>
            @if (session('status'))<p class="mt-4 rounded-lg border border-success-border bg-success-muted px-4 py-3 text-sm text-success">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="mt-4 rounded-lg border border-error-border bg-error-muted px-4 py-3 text-sm text-primary-hover">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('portal.login.attempt') }}" class="mt-6 space-y-5">
                @csrf
                <x-admin.input name="email" label="E-mail" type="email" required autofocus autocomplete="username" />
                <x-admin.input name="password" label="Senha" type="password" required autocomplete="current-password" />
                <x-admin.button type="submit" variant="primary" class="w-full">Entrar</x-admin.button>
            </form>
        </div>
    </main>
</x-layouts.admin-auth>
