<x-layouts.admin-auth title="Entrar no painel | José Carlos">
    <div class="grid min-h-dvh lg:grid-cols-[minmax(0,460px)_1fr]">

        {{-- ─── Painel de marca (esquerda, escuro) — some no mobile ─── --}}
        <aside
            class="relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-12"
            style="background:
                radial-gradient(70% 55% at 20% 10%, rgba(37, 99, 235, 0.25), transparent 60%),
                radial-gradient(60% 50% at 100% 100%, rgba(139, 92, 246, 0.20), transparent 55%),
                linear-gradient(160deg, #0b1220 0%, #121a2e 55%, #0b1120 100%);"
        >
            {{-- topo: marca --}}
            <div class="relative z-10 flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/10 ring-1 ring-white/15">
                    <img src="{{ asset('animated/laravel-animated.svg') }}" alt="" class="h-5 w-5">
                </span>
                <span class="text-sm font-semibold tracking-tight">José Carlos</span>
            </div>

            {{-- meio: citação --}}
            <div class="relative z-10 max-w-sm">
                <blockquote class="font-serif text-3xl font-medium italic leading-snug text-white xl:text-4xl">
                    “Do banco ao deploy, com código que se explica.”
                </blockquote>
                <p class="mt-6 text-sm leading-relaxed text-white/60">
                    Gerencie os projetos, a formação e o conteúdo do portfólio — tudo em um só lugar.
                </p>
            </div>

            {{-- base: itens de confiança --}}
            <ul class="relative z-10 space-y-4 text-sm text-white/70">
                <li class="flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    </span>
                    Acesso restrito a administradores
                </li>
                <li class="flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    Sessão protegida e limite de tentativas
                </li>
                <li class="flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.22 2 .78 2.5a2 2 0 0 0 2.6 1.3l2.4-.9 1.9 1.9-.9 2.4a2 2 0 0 0 1.3 2.6l2.5.78v2.68l-2.5.78a2 2 0 0 0-1.3 2.6l.9 2.4-1.9 1.9-2.4-.9a2 2 0 0 0-2.6 1.3l-.78 2.5H9.54l-.78-2.5a2 2 0 0 0-2.6-1.3l-2.4.9-1.9-1.9.9-2.4a2 2 0 0 0-1.3-2.6L-.5 13.46v-2.92"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    Gerenciamento de todo o conteúdo do site
                </li>
            </ul>
        </aside>

        {{-- ─── Formulário (direita, claro) ─── --}}
        <main class="flex items-center justify-center px-6 py-12 sm:px-10">
            <div class="w-full max-w-sm">
                {{-- marca no mobile --}}
                <div class="mb-10 flex items-center gap-2.5 lg:hidden">
                    <span class="grid h-9 w-9 place-items-center rounded-xl border border-border bg-card">
                        <img src="{{ asset('animated/laravel-animated.svg') }}" alt="" class="h-5 w-5">
                    </span>
                    <span class="text-sm font-semibold tracking-tight">José Carlos</span>
                </div>

                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-foreground-muted">Painel Administrativo</p>
                <h1 class="mt-2 font-incognito text-3xl font-bold tracking-tight">Acesse o painel</h1>
                <p class="mt-2 text-sm text-foreground-secondary">Informe suas credenciais para gerenciar o portfólio.</p>

                @if ($errors->any())
                    <div role="alert" aria-live="assertive" class="mt-6 flex items-start gap-2.5 rounded-lg border border-error-border bg-error-muted px-4 py-3 text-sm text-error">
                        <svg aria-hidden="true" class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        <div class="space-y-0.5">
                            @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-7 space-y-5" novalidate data-login-form>
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-foreground-secondary">E-mail</label>
                        <div class="relative">
                            <span aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-foreground-muted">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" inputmode="email" placeholder="voce@exemplo.com"
                                aria-describedby="email-error" @if ($errors->has('email')) aria-invalid="true" @endif
                                class="w-full rounded-lg border border-border bg-card py-3 pl-11 pr-4 text-sm text-foreground outline-none transition-colors placeholder:text-foreground-muted hover:border-border-hover focus:border-primary focus:ring-2 focus:ring-primary-muted aria-[invalid=true]:border-error">
                        </div>
                        <p id="email-error" data-error-for="email" class="mt-1.5 hidden text-xs text-error"></p>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-foreground-secondary">Senha</label>
                        <div class="relative">
                            <span aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-foreground-muted">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••"
                                aria-describedby="password-error"
                                class="w-full rounded-lg border border-border bg-card py-3 pl-11 pr-11 text-sm text-foreground outline-none transition-colors placeholder:text-foreground-muted hover:border-border-hover focus:border-primary focus:ring-2 focus:ring-primary-muted aria-[invalid=true]:border-error">
                            <button type="button" data-toggle-password aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute right-2.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-foreground-muted transition-colors hover:text-foreground-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-muted">
                                <svg data-icon-show class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg data-icon-hide class="hidden h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                            </button>
                        </div>
                        <p id="password-error" data-error-for="password" class="mt-1.5 hidden text-xs text-error"></p>
                    </div>

                    <button type="submit" data-submit
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg data-spinner aria-hidden="true" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span data-label>Entrar no painel</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-foreground-muted">© {{ now()->year }} José Carlos. Área restrita.</p>
            </div>
        </main>
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-login-form]');
            if (!form) return;
            const email = form.querySelector('#email');
            const password = form.querySelector('#password');
            const submit = form.querySelector('[data-submit]');
            const spinner = submit.querySelector('[data-spinner]');
            const label = submit.querySelector('[data-label]');

            const toggle = form.querySelector('[data-toggle-password]');
            const iconShow = toggle.querySelector('[data-icon-show]');
            const iconHide = toggle.querySelector('[data-icon-hide]');
            toggle.addEventListener('click', () => {
                const visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!visible));
                toggle.setAttribute('aria-label', visible ? 'Mostrar senha' : 'Ocultar senha');
                iconShow.classList.toggle('hidden', !visible);
                iconHide.classList.toggle('hidden', visible);
                password.focus();
            });

            function setError(field, message) {
                const box = form.querySelector('[data-error-for="' + field.id + '"]');
                if (message) { field.setAttribute('aria-invalid', 'true'); if (box) { box.textContent = message; box.classList.remove('hidden'); } }
                else { field.removeAttribute('aria-invalid'); if (box) { box.textContent = ''; box.classList.add('hidden'); } }
            }
            function validate() {
                let ok = true;
                const v = email.value.trim();
                if (!v) { setError(email, 'Informe o seu e-mail.'); ok = false; }
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { setError(email, 'Digite um e-mail válido.'); ok = false; }
                else { setError(email, null); }
                if (!password.value) { setError(password, 'Informe a sua senha.'); ok = false; } else { setError(password, null); }
                return ok;
            }
            [email, password].forEach((f) => f.addEventListener('input', () => { if (f.getAttribute('aria-invalid') === 'true') setError(f, null); }));

            let submitting = false;
            form.addEventListener('submit', (e) => {
                if (submitting) { e.preventDefault(); return; }
                if (!validate()) { e.preventDefault(); form.querySelector('[aria-invalid="true"]')?.focus(); return; }
                submitting = true; submit.disabled = true; spinner.classList.remove('hidden'); label.textContent = 'Entrando…';
            });
        })();
    </script>
</x-layouts.admin-auth>
