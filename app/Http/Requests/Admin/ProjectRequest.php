<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;
use App\Services\PortfolioContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * Normaliza a entrada: tecnologias chegam como texto ("Laravel, PHP, Vue 3"), os checkboxes desmarcados
     * não são enviados pelo navegador e o slug informado passa por `Str::slug`.
     */
    protected function prepareForValidation(): void
    {
        $technologies = $this->input('technologies');

        $this->merge([
            'technologies' => is_string($technologies) ? $this->splitList($technologies) : $technologies,
            'featured' => $this->boolean('featured'),
            'is_active' => $this->boolean('is_active'),
            'slug' => filled($this->input('slug')) ? Str::slug((string) $this->input('slug')) : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $image = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($project?->id)],
            'acronym' => ['nullable', 'string', 'max:6'],
            'description' => ['required', 'string', 'max:1000'],
            'overview' => ['nullable', 'string', 'max:10000'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'string', 'max:10000'],
            'architecture' => ['nullable', 'string', 'max:10000'],
            'development' => ['nullable', 'string', 'max:10000'],
            'challenges' => ['nullable', 'array', 'max:20'],
            'challenges.*.title' => ['nullable', 'string', 'max:255'],
            'challenges.*.problem' => ['nullable', 'string', 'max:3000'],
            'challenges.*.solution' => ['nullable', 'string', 'max:3000'],
            'conclusion' => ['nullable', 'string', 'max:5000'],
            'extra_sections' => ['nullable', 'json', 'max:50000'],
            'technologies' => ['nullable', 'array', 'max:30'],
            'technologies.*' => ['string', 'max:50'],
            'github_url' => ['nullable', 'url:http,https', 'max:255'],
            'demo_url' => ['nullable', 'url:http,https', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'image' => $image,
            'remove_image' => ['boolean'],
            'screenshots' => ['nullable', 'array', 'max:30'],
            'screenshots.*.src' => ['required_with:screenshots', 'string', 'max:255'],
            'screenshots.*.alt' => ['nullable', 'string', 'max:255'],
            'screenshots.*.caption' => ['nullable', 'string', 'max:500'],
            'screenshots.*.remove' => ['nullable', 'boolean'],
            'gallery_files' => ['nullable', 'array', 'max:10'],
            'gallery_files.*' => $image,
            'featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image' => $image,
            'remove_og_image' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateExtraSections($validator);
                $this->validateFeaturedLimit($validator);
            },
        ];
    }

    /** Seções extras (JSON avançado): lista de objetos com `title` e, opcionalmente, paragraphs/items/note/flow. */
    private function validateExtraSections(Validator $validator): void
    {
        if (blank($this->input('extra_sections'))) {
            return;
        }

        $sections = json_decode((string) $this->input('extra_sections'), true);

        if (! is_array($sections) || ! array_is_list($sections)) {
            $validator->errors()->add('extra_sections', 'As seções extras precisam ser uma lista JSON ([ {...}, {...} ]).');

            return;
        }

        foreach ($sections as $index => $section) {
            if (! is_array($section) || ! is_string($section['title'] ?? null) || trim($section['title']) === '') {
                $validator->errors()->add('extra_sections', 'A seção extra #'.($index + 1).' precisa ter um "title" em texto.');

                return;
            }

            foreach (['paragraphs', 'items'] as $listKey) {
                if (isset($section[$listKey]) && ! (is_array($section[$listKey]) && array_is_list($section[$listKey]))) {
                    $validator->errors()->add('extra_sections', 'Na seção extra #'.($index + 1).', "'.$listKey.'" precisa ser uma lista de textos.');

                    return;
                }
            }
        }
    }

    /** Respeita o limite de destaques definido em Configurações. */
    private function validateFeaturedLimit(Validator $validator): void
    {
        if (! $this->boolean('featured')) {
            return;
        }

        /** @var Project|null $project */
        $project = $this->route('project');
        $limit = (int) app(PortfolioContent::class)->setting('featured_projects_limit');
        $others = Project::where('featured', true)->when($project, fn ($query) => $query->whereKeyNot($project->id))->count();

        if ($limit > 0 && $others >= $limit) {
            $validator->errors()->add('featured', "Limite de {$limit} projetos em destaque atingido. Remova o destaque de outro projeto ou aumente o limite em Configurações.");
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'nome',
            'slug' => 'slug',
            'acronym' => 'sigla',
            'description' => 'descrição curta',
            'overview' => 'descrição completa',
            'image' => 'imagem principal',
            'technologies' => 'tecnologias',
            'github_url' => 'GitHub',
            'demo_url' => 'Live URL',
            'project_date' => 'data do projeto',
            'sort_order' => 'ordem',
            'extra_sections' => 'seções extras',
            'gallery_files.*' => 'imagem da galeria',
            'og_image' => 'imagem OG',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function splitList(string $value): array
    {
        return collect(preg_split('/[,\n]/', $value))
            ->map(fn (string $item) => Str::squish($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
