<?php

namespace App\Http\Controllers\Admin;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Model;

class ExperienceController extends ResourceController
{
    protected function modelClass(): string
    {
        return Experience::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.experiences';
    }

    protected function resourceName(): string
    {
        return 'Experiência';
    }

    protected function labels(): array
    {
        return ['singular' => 'experiência', 'plural' => 'Experiência', 'feminine' => true];
    }

    protected function searchable(): array
    {
        return ['role', 'company', 'location'];
    }

    protected function itemLabel(Model $model): string
    {
        return $model->role.' — '.$model->company;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Logo', 'type' => 'image', 'render' => fn (Experience $e) => $e->logo],
            ['label' => 'Cargo', 'sort' => 'role', 'render' => fn (Experience $e) => $e->role, 'primary' => true],
            ['label' => 'Empresa', 'sort' => 'company', 'render' => fn (Experience $e) => $e->company],
            ['label' => 'Período', 'sort' => 'start_date', 'render' => fn (Experience $e) => $e->period_label ?? '—'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'role', 'label' => 'Cargo', 'type' => 'text', 'required' => true],
            ['name' => 'company', 'label' => 'Empresa', 'type' => 'text', 'required' => true],
            ['name' => 'location', 'label' => 'Localização', 'type' => 'text'],
            ['name' => 'url', 'label' => 'Site da empresa', 'type' => 'url'],
            ['name' => 'start_date', 'label' => 'Data inicial', 'type' => 'date'],
            ['name' => 'end_date', 'label' => 'Data final', 'type' => 'date', 'help' => 'Deixe em branco se ainda não terminou.'],
            ['name' => 'is_current', 'label' => 'Atual', 'type' => 'checkbox', 'help' => 'Sem data final, o período aparece como "Presente".'],
            ['name' => 'period_note', 'label' => 'Observação do período', 'type' => 'text', 'help' => 'Opcional. Ex.: Temporário.'],
            ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'rows' => 5, 'wide' => true],
            ['name' => 'technologies', 'label' => 'Tecnologias', 'type' => 'lines', 'help' => 'Uma por linha.'],
            ['name' => 'responsibilities', 'label' => 'Responsabilidades', 'type' => 'lines', 'help' => 'Uma por linha.'],
            ['name' => 'results', 'label' => 'Resultados', 'type' => 'lines', 'help' => 'Uma por linha. Só resultados reais.'],
            ['name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'help' => 'JPG, PNG ou WebP até 2 MB.'],
            ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'help' => 'Em branco = no final. Também dá para reordenar na listagem.'],
            ['name' => 'is_published', 'label' => 'Publicado', 'type' => 'checkbox', 'help' => 'Rascunhos não aparecem no site.'],
        ];
    }

    protected function rules(?Model $model): array
    {
        return [
            'role' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
            'period_note' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'string', 'max:2000'],
            'responsibilities' => ['nullable', 'string', 'max:5000'],
            'results' => ['nullable', 'string', 'max:5000'],
            'logo' => $this->imageRules(),
            'is_published' => ['boolean'],
        ];
    }
}
