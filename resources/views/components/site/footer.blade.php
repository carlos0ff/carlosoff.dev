<footer class="mt-24 border-t border-white/5">
    <div class="mx-auto max-w-[1240px] px-6 sm:px-14 py-12">
        <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
            {{-- Marca --}}
            <div class="max-w-xs">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-7 w-7 place-items-center rounded-md bg-blue-600 text-sm font-bold text-white font-display">C</span>
                    <span class="font-display text-sm font-semibold tracking-tight text-white">José Carlos</span>
                </a>
                <p class="mt-4 text-sm leading-relaxed text-zinc-500">
                    Desenvolvedor backend em PHP e Laravel. Do conceito ao deploy, com código que se explica.
                </p>
            </div>

            {{-- Nav --}}
            <nav>
                <ul class="space-y-2.5 font-mono2 text-xs text-zinc-400">
                    <li><a href="{{ route('home') }}" class="hover:text-white">Início</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-white">Sobre</a></li>
                    <li><a href="{{ route('projects.index') }}" class="hover:text-white">Projetos</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white">Contato</a></li>
                </ul>
            </nav>

            {{-- Social (apenas o GitHub é link real) --}}
            <ul class="space-y-2.5 font-mono2 text-xs text-zinc-400">
                <li>
                    <a href="https://github.com/carlos0ff" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-white">
                        GitHub <span aria-hidden="true">↗</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-1 hover:text-white">
                        Contato <span aria-hidden="true">→</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="mt-10 border-t border-white/5 pt-6">
            <p class="font-mono2 text-xs text-zinc-600">© {{ now()->year }} José Carlos — Desenvolvido e codado pela mesma pessoa.</p>
        </div>
    </div>
</footer>
