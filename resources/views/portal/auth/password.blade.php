<x-layouts.admin-auth title="Definir senha | Portal do cliente">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-2xl border border-border bg-card p-8 shadow-lg shadow-black/30">
            <h1 class="font-incognito text-2xl font-bold tracking-tight">Definir senha</h1>
            <p class="mt-2 text-sm text-foreground-secondary">Mínimo de 10 caracteres, com maiúsculas, minúsculas e números.</p>
            @if ($errors->any())<div role="alert" class="mt-4 rounded-lg border border-error-border bg-error-muted px-4 py-3 text-sm text-primary-hover">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('portal.password.store') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-admin.input name="email" label="E-mail" type="email" :value="$email" required />
                <x-admin.input name="password" label="Nova senha" type="password" required autocomplete="new-password" />
                <x-admin.input name="password_confirmation" label="Confirmar senha" type="password" required autocomplete="new-password" />
                <x-admin.button type="submit" variant="primary" class="w-full">Salvar senha</x-admin.button>
            </form>
        </div>
    </main>
</x-layouts.admin-auth>
