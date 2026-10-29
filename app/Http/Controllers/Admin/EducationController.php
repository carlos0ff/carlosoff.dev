<?php

namespace App\Http\Controllers\Admin;

use App\Models\Education;
use Illuminate\Database\Eloquent\Model;

class EducationController extends ResourceController
{
    protected function modelClass(): string
    {
        return Education::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.education';
    }

    protected function resourceName(): string
    {
        return 'Formação';
    }

    protected function labels(): array
    {
        return ['singular' => 'formação', 'plural' => 'Formação Acadêmica', 'feminine' => true];
    }

    protected function searchable(): array
    {
        return ['course', 'institution', 'degree'];
    }

    protected function itemLabel(Model $model): string
    {
        return $model->course.' — '.$model->institution;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Logo', 'type' => 'image', 'render' => fn (Education $e) => $e->logo],
            ['label' => 'Curso', 'sort' => 'course', 'render' => fn (Education $e) => $e->title, 'primary' => true],
            ['label' => 'Instituição', 'sort' => 'institution', 'render' => fn (Education $e) => $e->institution],
            ['label' => 'Período', 'sort' => 'start_date', 'render' => fn (Education $e) => $e->period_label ?? '—'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'course', 'label' => 'Curso', 'type' => 'text', 'required' => true],
            ['name' => 'institution', 'label' => 'Instituição', 'type' => 'text', 'required' => true],
            ['name' => 'degree', 'label' => 'Grau', 'type' => 'text', 'help' => 'Opcional. Ex.: Bacharelado. É exibido depois do curso.'],
            ['name' => 'location', 'label' => 'Localização', 'type' => 'text'],
            ['name' => 'start_date', 'label' => 'Data inicial', 'type' => 'date'],
            ['name' => 'end_date', 'label' => 'Data final', 'type' => 'date', 'help' => 'Pode ser a previsão de término.'],
            ['name' => 'in_progress', 'label' => 'Em andamento', 'type' => 'checkbox'],
            ['name' => 'period_note', 'label' => 'Observação do período', 'type' => 'text', 'help' => 'Opcional. Ex.: Trancada.'],
            ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'rows' => 6, 'wide' => true],
            ['name' => 'skills', 'label' => 'Competências', 'type' => 'lines', 'help' => 'Uma por linha.'],
            ['name' => 'url', 'label' => 'Site da instituição', 'type' => 'url'],
            ['name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'help' => 'JPG, PNG ou WebP até 2 MB.'],
            ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'help' => 'Em branco = no final.'],
            ['name' => 'is_published', 'label' => 'Publicado', 'type' => 'checkbox', 'help' => 'Rascunhos não aparecem no site.'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'course' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'in_progress' => ['boolean'],
            'period_note' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => $this->imageRules(),
            'is_published' => ['boolean'],
        ];
    }
}
