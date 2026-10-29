<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TechnologyController extends ResourceController
{
    protected function modelClass(): string
    {
        return Technology::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.technologies';
    }

    protected function resourceName(): string
    {
        return 'Tecnologia';
    }

    protected function labels(): array
    {
        return ['singular' => 'tecnologia', 'plural' => 'Stack / Tecnologias', 'feminine' => true];
    }

    protected function searchable(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function itemLabel(Model $model): string
    {
        return $model->name;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Ícone', 'type' => 'image', 'render' => fn (Technology $t) => $t->icon],
            ['label' => 'Nome', 'sort' => 'name', 'render' => fn (Technology $t) => $t->name, 'primary' => true],
            ['label' => 'Categoria', 'sort' => 'category', 'render' => fn (Technology $t) => $t->category->label()],
            ['label' => 'Destaque', 'sort' => 'featured', 'render' => fn (Technology $t) => $t->featured ? '★ Sim' : '—'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nome', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'help' => 'Em branco = gerado a partir do nome.'],
            ['name' => 'category', 'label' => 'Categoria', 'type' => 'select', 'required' => true, 'options' => TechnologyCategory::options()],
            ['name' => 'official_url', 'label' => 'URL oficial', 'type' => 'url'],
            ['name' => 'level', 'label' => 'Nível', 'type' => 'text', 'help' => 'Opcional. Ex.: Básico, Intermediário, Avançado.'],
            ['name' => 'years_experience', 'label' => 'Anos de experiência', 'type' => 'number', 'help' => 'Só preencha se for uma informação real.'],
            ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'rows' => 3, 'wide' => true],
            ['name' => 'icon', 'label' => 'Ícone', 'type' => 'image', 'help' => 'JPG, PNG ou WebP até 2 MB. Sem ícone, o site usa um ícone padrão.'],
            ['name' => 'invert_icon_in_dark', 'label' => 'Inverter ícone no tema escuro', 'type' => 'checkbox', 'help' => 'Para ícones pretos (ex.: GitHub).'],
            ['name' => 'featured', 'label' => 'Destaque', 'type' => 'checkbox'],
            ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'help' => 'Em branco = no final.'],
            ['name' => 'is_published', 'label' => 'Publicado', 'type' => 'checkbox', 'help' => 'Desativadas não aparecem no site.'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('technologies', 'slug')->ignore($model?->id)],
            'category' => ['required', Rule::enum(TechnologyCategory::class)],
            'official_url' => ['nullable', 'url:http,https', 'max:255'],
            'level' => ['nullable', 'string', 'max:50'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => $this->imageRules(),
            'invert_icon_in_dark' => ['boolean'],
            'featured' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    protected function prepare(array $data, ?Model $model): array
    {
        // Sem slug informado: mantém o atual (edição) ou deixa o model gerar (criação).
        if (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        return $data;
    }
}
