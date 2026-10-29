@php
    $links = old('links', $about['links'] ?? []);
    $linkRow = fn ($index, $link = []) => view('admin.about.link-row', ['index' => $index, 'link' => $link])->render();
@endphp

<x-layouts.admin title="Sobre" :breadcrumbs="['Sobre' => null]">
    <x-admin.page-header title="Sobre" subtitle="Conteúdo da página pública /sobre.">
        <x-admin.button :href="route('about')" target="_blank" rel="noopener">Ver página ↗</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.about.update') }}" enctype="multipart/form-data" novalidate data-about-form class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.card title="Apresentação">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="name" label="Nome" :value="$about['name']" required />
                <x-admin.input name="headline" label="Título" :value="$about['headline']" required class="md:col-span-2" />
                <x-admin.input name="subtitle" label="Subtítulo" :value="$about['subtitle']" class="md:col-span-2" />
                <x-admin.textarea name="description" label="Descrição" :value="$about['description']" :rows="3" class="md:col-span-2" help="Texto de destaque, exibido antes da biografia." />
                <x-admin.textarea name="biography" label="Biografia" :value="$about['biography']" :rows="9" class="md:col-span-2" help="Separe os parágrafos com uma linha em branco." />
                <x-admin.textarea name="quote" label="Citação" :value="$about['quote']" :rows="3" class="md:col-span-2" help="Aparece no bloco em destaque abaixo da biografia." />
            </div>
        </x-admin.card>

        <x-admin.card title="Perfil">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="location" label="Localização" :value="$about['location']" />
                <x-admin.input name="professional_status" label="Status profissional" :value="$about['professional_status']" help="Ex.: Disponível para novos projetos." />
                <x-admin.input name="email" label="E-mail" type="email" :value="$about['email']" help="Em branco, o e-mail não aparece no site." class="md:col-span-2" />
            </div>
        </x-admin.card>

        <x-admin.card title="Foto e currículo">
            <div class="grid gap-6">
                <div class="grid gap-4">
                    <x-admin.file name="photo_file" label="Foto (enviar arquivo)" :current="$about['photo']" remove-name="remove_photo" help="JPG, PNG ou WebP até 4 MB. Um novo envio substitui a foto atual." />
                    <x-admin.input name="photo" label="ou endereço da foto" :value="$about['photo']" help="URL (https://...) ou caminho já existente (image/..., storage/...)." />
                </div>

                <div class="grid gap-4">
                    <x-admin.file name="resume_file" label="Currículo (enviar PDF)" :current="$about['resume']" accept="application/pdf" remove-name="remove_resume" help="PDF até 5 MB. Sem currículo, o botão fica desativado no site." />
                    <x-admin.input name="resume" label="ou endereço do currículo" :value="$about['resume']" help="URL (https://...) ou caminho (docs/curriculo.pdf)." />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Links">
            <div data-repeater>
                <div data-repeater-list class="space-y-3">
                    @foreach ($links as $index => $link)
                        {!! $linkRow($index, $link) !!}
                    @endforeach
                </div>

                <template data-repeater-template>{!! $linkRow('__INDEX__') !!}</template>

                <x-admin.button data-repeater-add class="mt-4" size="sm">+ Adicionar link</x-admin.button>
            </div>
        </x-admin.card>

        <x-admin.card title="SEO (opcional)">
            <div class="grid gap-5 md:grid-cols-2">
                <x-admin.input name="seo_title" label="SEO title" :value="$about['seo_title']" />
                <x-admin.input name="og_title" label="OG title" :value="$about['og_title']" />
                <x-admin.textarea name="seo_description" label="Meta description" :value="$about['seo_description']" :rows="3" help="Até 320 caracteres." />
                <x-admin.textarea name="og_description" label="OG description" :value="$about['og_description']" :rows="3" help="Até 320 caracteres." />
                <x-admin.file name="og_image" label="OG image" :current="$about['og_image']" remove-name="remove_og_image" class="md:col-span-2" help="JPG, PNG ou WebP até 4 MB." />
            </div>
        </x-admin.card>

        <div class="flex flex-wrap items-center gap-3">
            <x-admin.button type="submit" variant="primary">Salvar alterações</x-admin.button>
        </div>
    </form>
</x-layouts.admin>
