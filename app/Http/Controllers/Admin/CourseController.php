<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseType;
use App\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CourseController extends ResourceController
{
    protected function modelClass(): string
    {
        return Course::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.courses';
    }

    protected function resourceName(): string
    {
        return 'Curso/Certificação';
    }

    protected function labels(): array
    {
        return ['singular' => 'item', 'plural' => 'Cursos e Certificações', 'feminine' => false];
    }

    protected function searchable(): array
    {
        return ['name', 'institution', 'credential'];
    }

    protected function itemLabel(Model $model): string
    {
        return $model->name.' — '.$model->institution;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Nome', 'sort' => 'name', 'render' => fn (Course $c) => $c->name, 'primary' => true],
            ['label' => 'Instituição', 'sort' => 'institution', 'render' => fn (Course $c) => $c->institution],
            ['label' => 'Tipo', 'sort' => 'type', 'render' => fn (Course $c) => $c->type->label()],
            ['label' => 'Conclusão', 'sort' => 'completed_at', 'render' => fn (Course $c) => $c->completed_at?->format('d/m/Y') ?? '—'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nome', 'type' => 'text', 'required' => true],
            ['name' => 'institution', 'label' => 'Instituição', 'type' => 'text', 'required' => true],
            ['name' => 'type', 'label' => 'Tipo', 'type' => 'select', 'required' => true, 'options' => CourseType::options()],
            ['name' => 'workload_hours', 'label' => 'Carga horária (horas)', 'type' => 'number'],
            ['name' => 'issued_at', 'label' => 'Data de emissão', 'type' => 'date'],
            ['name' => 'completed_at', 'label' => 'Data de conclusão', 'type' => 'date', 'help' => 'É a data exibida no site (ou a emissão, se não houver).'],
            ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
            ['name' => 'certificate_url', 'label' => 'URL do certificado', 'type' => 'url'],
            ['name' => 'credential', 'label' => 'Credencial (ID/código)', 'type' => 'text'],
            ['name' => 'image', 'label' => 'Imagem do certificado', 'type' => 'image', 'help' => 'Abre no modal "Ver certificado". JPG, PNG ou WebP até 5 MB.'],
            ['name' => 'logo', 'label' => 'Logo da instituição', 'type' => 'image', 'help' => 'Opcional. Sem logo, o site usa um ícone padrão.'],
            ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'help' => 'Em branco = no final.'],
            ['name' => 'is_published', 'label' => 'Publicado', 'type' => 'checkbox', 'help' => 'Rascunhos não aparecem no site.'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CourseType::class)],
            'workload_hours' => ['nullable', 'integer', 'min:1', 'max:65000'],
            'issued_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'description' => ['nullable', 'string', 'max:2000'],
            'certificate_url' => ['nullable', 'url:http,https', 'max:500'],
            'credential' => ['nullable', 'string', 'max:255'],
            'image' => $this->imageRules(5120),
            'logo' => $this->imageRules(),
            'is_published' => ['boolean'],
        ];
    }
}
