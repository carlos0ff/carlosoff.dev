<x-layouts.site title="Contato | José Carlos" description="Vamos conversar sobre o seu próximo projeto." active="contact">
    <section data-reveal class="mx-auto max-w-[1240px] px-6 sm:px-14 pt-20 pb-16">
        <p class="font-mono2 text-xs tracking-wider text-blue-400">/ CONTATO</p>
        <h1 class="mt-4 font-display text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">Vamos conversar?</h1>
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-zinc-400">
            Tem um projeto em mente ou quer trocar uma ideia sobre backend, PHP e Laravel? Me chama por um dos canais abaixo.
        </p>

        <div class="mt-10 grid gap-4 sm:grid-cols-2">
            <a href="https://github.com/carlos0ff" target="_blank" rel="noopener"
               class="group flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.02] p-6 transition-colors hover:border-blue-500/40">
                <div>
                    <p class="font-mono2 text-xs text-blue-400">/ github</p>
                    <p class="mt-1 font-display text-lg font-semibold text-white">@carlos0ff</p>
                    <p class="mt-1 text-sm text-zinc-500">Meus repositórios e contribuições.</p>
                </div>
                <span aria-hidden="true" class="font-mono2 text-xl text-zinc-600 transition-colors group-hover:text-blue-400">↗</span>
            </a>

            <a href="{{ route('projects.index') }}"
               class="group flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.02] p-6 transition-colors hover:border-blue-500/40">
                <div>
                    <p class="font-mono2 text-xs text-blue-400">/ projetos</p>
                    <p class="mt-1 font-display text-lg font-semibold text-white">Ver meu trabalho</p>
                    <p class="mt-1 text-sm text-zinc-500">Estudos de caso, do conceito ao deploy.</p>
                </div>
                <span aria-hidden="true" class="font-mono2 text-xl text-zinc-600 transition-colors group-hover:text-blue-400">→</span>
            </a>
        </div>
    </section>
</x-layouts.site>
