@props(['steps' => [], 'caption' => null])

{{-- Fluxo de dados em etapas, de cima para baixo. `status` = "planned" desenha a etapa tracejada com a etiqueta "Planejado". --}}
<figure>
    <ol class="flex flex-col" aria-label="Etapas do fluxo">
        @foreach ($steps as $step)
            @php($planned = ($step['status'] ?? 'implemented') === 'planned')

            <li data-flow-step @if ($planned) data-flow-planned @endif style="--reveal-index: {{ $loop->index + 4 }}" class="experience-reveal flex flex-col items-center">
                <div @class([
                    'flex w-full items-start gap-4 rounded-xl border p-4 sm:p-5',
                    'dark:border-zinc-800 border-zinc-200 dark:bg-white/5 bg-white' => ! $planned,
                    'border-dashed dark:border-zinc-700 border-zinc-300' => $planned,
                ])>
                    <span aria-hidden="true" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-primary-color/50 text-sm font-semibold tabular-nums text-primary-color">{{ $loop->iteration }}</span>

                    <div class="min-w-0">
                        <h3 class="font-incognito flex flex-wrap items-center gap-x-3 gap-y-1 text-base font-semibold leading-snug">
                            {{ $step['label'] }}
                            @if ($planned)
                                <span class="rounded-full border border-dashed dark:border-zinc-600 border-zinc-400 px-2 py-0.5 font-sans text-[10px] font-medium uppercase tracking-wider dark:text-zinc-400 text-zinc-600">Planejado</span>
                            @endif
                        </h3>
                        @if (! empty($step['detail']))
                            <p class="mt-1 text-sm leading-relaxed dark:text-zinc-400 text-zinc-600">{{ $step['detail'] }}</p>
                        @endif
                    </div>
                </div>

                @unless ($loop->last)
                    <span aria-hidden="true" class="flex h-8 flex-col items-center text-primary-color">
                        <span class="w-px flex-1 bg-primary-color/40"></span>
                        <span class="-mt-1 text-xs leading-none">▼</span>
                    </span>
                @endunless
            </li>
        @endforeach
    </ol>

    @if ($caption)
        <figcaption class="mt-4 text-sm leading-relaxed dark:text-zinc-500 text-zinc-500">{{ $caption }}</figcaption>
    @endif
</figure>
