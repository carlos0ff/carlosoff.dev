@php
    $isEdit = $project->exists;
    $details = $project->details ?? [];
    $title = $isEdit ? 'Editar projeto' : 'Novo projeto';

    $joinParagraphs = fn ($value) => is_array($value) ? implode("\n\n", $value) : (string) $value;
    $joinLines = fn ($value) => is_array($value) ? implode("\n", $value) : (string) $value;

    $technologies = old('technologies', $project->technologies ?? []);
    $technologies = is_array($technologies) ? implode(', ', $technologies) : $technologies;

    $challenges = old('challenges', $details['challenges'] ?? []);
    $screenshots = old('screenshots', $details['screenshots'] ?? []);
    $extraSections = old('extra_sections', ! empty($details['sections']) ? json_encode($details['sections'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');
    $stack = \App\Models\Technology::published()->ordered()->pluck('name')->implode(', ');

    $challengeRow = fn ($index, $challenge = []) => view('admin.projects.challenge-row', ['index' => $index, 'challenge' => $challenge])->render();
@endphp

<x-layouts.admin :title="$title" :breadcrumbs="['Projetos' => route('admin.projects.index'), ($isEdit ? $project->title : 'Novo') => null]">
    <x-admin.page-header :title="$title">
        @if ($isEdit && $project->hasDetailPage())
            <x-admin.button :href="route('admin.projects.preview', $project)">Visualizar página</x-admin.button>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $isEdit ? route('admin.projects.update', $project) : route('admin.projects.store') }}" enctype="multipart/form-data" novalidate data-project-form class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-admin.card title="Informações básicas">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="title" label="Nome" :value="$project->title" required data-slug-source="#f-slug" />
                <x-admin.input name="slug" label="Slug" :value="$project->slug" help="Endereço da página: /projetos/slug. Em branco = gerado a partir do nome." />
                <x-admin.input name="acronym" label="Sigla" :value="$project->acronym" maxlength="6" help="Até 6 letras. Aparece na capa quando o projeto não tem imagem." />
                <x-admin.input name="project_date" label="Data do projeto" type="date" :value="$project->project_date?->format('Y-m-d')" />
                <x-admin.textarea name="description" label="Descrição curta" :value="$project->description" required :rows="3" class="md:col-span-2" help="Aparece no card da lista de projetos e na meta description." />
                <x-admin.input name="technologies" label="Tecnologias" :value="$technologies" class="md:col-span-2" help="Separe por vírgula. Cadastradas no Stack: {{ $stack ?: 'nenhuma' }}." />
                <x-admin.input name="github_url" label="GitHub" type="url" :value="$project->github_url" placeholder="https://github.com/..." />
                <x-admin.input name="demo_url" label="Live URL" type="url" :value="$project->demo_url" placeholder="https://..." />
            </div>
        </x-admin.card>

        <x-admin.card title="Conteúdo da página do projeto">
            <p class="mb-5 text-sm text-foreground-secondary">Preencha só o que fizer sentido. Seções vazias não aparecem no site, e sem nenhum conteúdo aqui o projeto não tem página própria (só o card).</p>
            <div class="grid gap-5">
                <x-admin.textarea name="overview" label="Descrição completa / Visão geral" :value="$joinParagraphs($details['overview'] ?? '')" :rows="6" help="Separe os parágrafos com uma linha em branco." />
                <x-admin.textarea name="objective" label="Objetivo" :value="$details['objective'] ?? ''" :rows="3" />
                <x-admin.textarea name="features" label="Funcionalidades (features)" :value="$joinLines($details['features'] ?? '')" :rows="6" help="Uma por linha." />
                <x-admin.textarea name="architecture" label="Arquitetura" :value="$details['architecture'] ?? ''" :rows="5" help="Separe os parágrafos com uma linha em branco." />
                <x-admin.textarea name="development" label="Desenvolvimento" :value="$joinParagraphs($details['development'] ?? '')" :rows="5" help="Separe os parágrafos com uma linha em branco." />
                <x-admin.textarea name="conclusion" label="Conclusão" :value="$details['conclusion'] ?? ''" :rows="4" />
            </div>
        </x-admin.card>

        <x-admin.card title="Desafios e soluções">
            <div data-repeater>
                <div data-repeater-list class="space-y-4">
                    @foreach ($challenges as $index => $challenge)
                        {!! $challengeRow($index, $challenge) !!}
                    @endforeach
                </div>

                <template data-repeater-template>{!! $challengeRow('__INDEX__') !!}</template>

                <x-admin.button data-repeater-add class="mt-4" size="sm">+ Adicionar desafio</x-admin.button>
            </div>
        </x-admin.card>

        <x-admin.card title="Seções extras (avançado)">
            <x-admin.textarea
                name="extra_sections"
                label="JSON das seções"
                :value="$extraSections"
                :rows="10"
                style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8125rem"
                spellcheck="false"
                help='Lista de seções adicionais: [{"title":"...","paragraphs":["..."],"items":["..."],"note":"...","flow":{"steps":[{"label":"...","detail":"...","status":"planned"}],"caption":"..."}}]. Só "title" é obrigatório.'
            />
        </x-admin.card>

        <x-admin.card title="Imagens">
            <div class="grid gap-6">
                <x-admin.file name="image" label="Imagem principal (capa)" :current="$project->image_url" help="JPG, PNG ou WebP até 4 MB." remove-name="remove_image" />

                <div>
                    <p class="mb-1.5 text-sm font-medium">Galeria (screenshots)</p>

                    @if (count($screenshots) > 0)
                        <ul class="mb-4 space-y-3">
                            @foreach ($screenshots as $index => $shot)
                                <li class="grid gap-3 rounded-lg border border-border p-3 sm:grid-cols-[7rem_1fr]">
                                    <img src="{{ asset($shot['src'] ?? '') }}" alt="" loading="lazy" class="h-20 w-28 rounded-md border border-border bg-white object-cover object-top">
                                    <div class="grid min-w-0 gap-3">
                                        <input type="hidden" name="screenshots[{{ $index }}][src]" value="{{ $shot['src'] ?? '' }}">
                                        <x-admin.input :name="'screenshots['.$index.'][alt]'" label="Texto alternativo" :value="$shot['alt'] ?? ''" />
                                        <x-admin.input :name="'screenshots['.$index.'][caption]'" label="Legenda" :value="$shot['caption'] ?? ''" />
                                        <label class="flex items-center gap-2 text-xs text-foreground-secondary">
                                            <input type="checkbox" name="screenshots[{{ $index }}][remove]" value="1" @checked(! empty($shot['remove'])) class="accent-primary">
                                            Remover da galeria
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <x-admin.file name="gallery_files[]" label="Adicionar imagens à galeria" :multiple="true" help="Até 10 imagens por envio (JPG, PNG ou WebP, 4 MB cada). Depois de salvar, edite o texto alternativo e a legenda." />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Publicação">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.toggle name="is_active" label="Publicado" :checked="$project->is_active" help="Rascunhos não aparecem no site." />
                <x-admin.toggle name="featured" label="Destaque" :checked="$project->featured" help="Destaques vêm primeiro na lista e aparecem na Home." />
                <x-admin.input name="sort_order" label="Ordem" type="number" min="0" :value="$project->sort_order" help="Em branco = no final. Também dá para reordenar na listagem." />
            </div>
        </x-admin.card>

        <x-admin.card title="SEO (opcional)">
            <p class="mb-5 text-sm text-foreground-secondary">Em branco, o site usa "Nome — Projetos" e a descrição curta.</p>
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="seo_title" label="SEO title" :value="$project->seo_title" />
                <x-admin.input name="og_title" label="OG title" :value="$project->og_title" />
                <x-admin.textarea name="seo_description" label="Meta description" :value="$project->seo_description" :rows="3" help="Até 320 caracteres." />
                <x-admin.textarea name="og_description" label="OG description" :value="$project->og_description" :rows="3" help="Até 320 caracteres." />
                <x-admin.file name="og_image" label="OG image" :current="$project->og_image" help="Em branco, usa a capa do projeto. JPG, PNG ou WebP até 4 MB." remove-name="remove_og_image" class="md:col-span-2" />
            </div>
        </x-admin.card>

        <div class="flex flex-wrap items-center gap-3">
            <x-admin.button type="submit" variant="primary">{{ $isEdit ? 'Salvar alterações' : 'Criar projeto' }}</x-admin.button>
            <x-admin.button :href="route('admin.projects.index')" variant="ghost">Cancelar</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
