<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD administrativo genérico para conteúdos publicáveis e ordenáveis
 * (experiência, formação, stack, cursos e certificações).
 *
 * Cada controller concreto só declara o model, os rótulos, as colunas da listagem, os campos do
 * formulário e as regras de validação; busca, filtros, ordenação, paginação, upload, exclusão lógica,
 * publicar/despublicar, reordenação e auditoria ficam aqui.
 */
abstract class ResourceController extends Controller
{
    private const PER_PAGE = 15;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Prefixo das rotas, ex.: "admin.experiences". */
    abstract protected function routePrefix(): string;

    /** Nome do recurso no log de auditoria, ex.: "Experiência". */
    abstract protected function resourceName(): string;

    /**
     * @return array{singular: string, plural: string, feminine: bool}
     */
    abstract protected function labels(): array;

    /**
     * Colunas da listagem: label, render (Closure recebendo o model), sort (coluna opcional) e type (text|image).
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function columns(): array;

    /**
     * Campos do formulário: name, label, type (text|url|number|date|textarea|select|checkbox|lines|image), etc.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function fields(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(?Model $model): array;

    /** Título do item para mensagens e auditoria. */
    abstract protected function itemLabel(Model $model): string;

    /**
     * Upload de imagem: só formatos raster (SVG pode carregar script), até 2 MB e 4000×4000 px.
     *
     * @return array<int, mixed>
     */
    protected function imageRules(int $maxKilobytes = 2048): array
    {
        return ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKilobytes, 'dimensions:max_width=4000,max_height=4000'];
    }

    /** @return array<int, string> */
    protected function searchable(): array
    {
        return [];
    }

    /** Coluna de ordenação padrão da listagem. */
    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    /** @return array<string, string> Campos de imagem => pasta de upload. */
    protected function uploadFields(): array
    {
        return collect($this->fields())->where('type', 'image')->mapWithKeys(fn (array $field) => [$field['name'] => $this->modelClass()::UPLOAD_DIRECTORY])->all();
    }

    /**
     * Gancho para ajustes finais nos dados validados antes de gravar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, ?Model $model): array
    {
        return $data;
    }

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['published', 'draft', 'trashed'], true) ? $request->query('status') : 'all';
        $search = trim((string) $request->query('q'));

        $sortable = collect($this->columns())->pluck('sort')->filter()->push('sort_order', 'created_at', 'updated_at')->all();
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : $this->defaultSort();
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = $this->modelClass()::query()
            ->when($status === 'published', fn (Builder $q) => $q->where('is_published', true))
            ->when($status === 'draft', fn (Builder $q) => $q->where('is_published', false))
            ->when($status === 'trashed', fn (Builder $q) => $q->onlyTrashed())
            ->when($search !== '' && $this->searchable(), function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    foreach ($this->searchable() as $column) {
                        $inner->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderBy($sort, $direction)
            ->orderBy('id');

        return view('admin.resource.index', [
            'items' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'columns' => $this->columns(),
            'labels' => $this->labels(),
            'routePrefix' => $this->routePrefix(),
            'filters' => compact('status', 'search', 'sort', 'direction'),
            'canReorder' => $sort === 'sort_order' && $direction === 'asc' && $status !== 'trashed' && $search === '',
            'counts' => [
                'all' => $this->modelClass()::count(),
                'published' => $this->modelClass()::where('is_published', true)->count(),
                'draft' => $this->modelClass()::where('is_published', false)->count(),
                'trashed' => $this->modelClass()::onlyTrashed()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->form(new ($this->modelClass())(['is_published' => true]));
    }

    public function edit(string $id): View
    {
        return $this->form($this->modelClass()::withTrashed()->findOrFail($id));
    }

    private function form(Model $model): View
    {
        return view('admin.resource.form', [
            'model' => $model,
            'fields' => $this->fields(),
            'labels' => $this->labels(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->payload($request, null);
        $data['sort_order'] ??= $this->modelClass()::nextSortOrder();

        $model = $this->modelClass()::create($data);

        AuditLog::record(AuditAction::Create, $this->resourceName(), $model->id, $this->itemLabel($model));

        return redirect()->route($this->routePrefix().'.index')->with('status', $this->message('criado', 'criada'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $model = $this->modelClass()::withTrashed()->findOrFail($id);
        $wasPublished = (bool) $model->is_published;

        $data = $this->payload($request, $model);
        $data['sort_order'] ??= $model->sort_order;

        $model->update($data);

        AuditLog::record(AuditAction::Update, $this->resourceName(), $model->id, $this->itemLabel($model));

        if ($wasPublished !== (bool) $model->is_published) {
            AuditLog::record($model->is_published ? AuditAction::Publish : AuditAction::Unpublish, $this->resourceName(), $model->id, $this->itemLabel($model));
        }

        return redirect()->route($this->routePrefix().'.index')->with('status', $this->message('atualizado', 'atualizada'));
    }

    /** Exclusão lógica: o item vai para a lixeira e pode ser restaurado. Arquivos enviados são mantidos. */
    public function destroy(string $id): RedirectResponse
    {
        $model = $this->modelClass()::findOrFail($id);
        $model->delete();

        AuditLog::record(AuditAction::Delete, $this->resourceName(), $model->id, $this->itemLabel($model));

        return redirect()->route($this->routePrefix().'.index')->with('status', $this->message('excluído', 'excluída'));
    }

    public function restore(string $id): RedirectResponse
    {
        $model = $this->modelClass()::onlyTrashed()->findOrFail($id);
        $model->restore();

        AuditLog::record(AuditAction::Restore, $this->resourceName(), $model->id, $this->itemLabel($model));

        return redirect()->route($this->routePrefix().'.index', ['status' => 'trashed'])->with('status', $this->message('restaurado', 'restaurada'));
    }

    public function toggle(string $id): RedirectResponse
    {
        $model = $this->modelClass()::findOrFail($id);
        $model->update(['is_published' => ! $model->is_published]);

        AuditLog::record($model->is_published ? AuditAction::Publish : AuditAction::Unpublish, $this->resourceName(), $model->id, $this->itemLabel($model));

        return back()->with('status', $this->message($model->is_published ? 'publicado' : 'movido para rascunho', $model->is_published ? 'publicada' : 'movida para rascunho'));
    }

    /**
     * Troca o item com o vizinho e renumera tudo de 1..n (a ordem do painel é a ordem pública).
     */
    public function move(Request $request, string $id): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $model = $this->modelClass()::findOrFail($id);

        DB::transaction(function () use ($model, $direction): void {
            $items = $this->modelClass()::ordered()->get()->all();
            $position = array_search($model->id, array_column($items, 'id'), true);
            $neighbor = $direction === 'up' ? $position - 1 : $position + 1;

            if (isset($items[$neighbor])) {
                [$items[$position], $items[$neighbor]] = [$items[$neighbor], $items[$position]];
            }

            foreach ($items as $index => $item) {
                if ($item->sort_order !== $index + 1) {
                    $item->update(['sort_order' => $index + 1]);
                }
            }
        });

        AuditLog::record(AuditAction::Update, $this->resourceName(), $model->id, $this->itemLabel($model), 'Reordenou');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?Model $model): array
    {
        $rules = $this->rules($model) + ['sort_order' => ['nullable', 'integer', 'min:0', 'max:65535']];
        $validated = $request->validate($rules);
        $data = [];

        foreach ($this->fields() as $field) {
            $name = $field['name'];
            $type = $field['type'];

            $data[$name] = match ($type) {
                'checkbox' => $request->boolean($name),
                'lines' => $this->lines($validated[$name] ?? null),
                'image' => $model?->{$name},
                default => isset($validated[$name]) && $validated[$name] !== '' ? $validated[$name] : null,
            };
        }

        foreach ($this->uploadFields() as $name => $directory) {
            $data[$name] = $this->handleUpload($request, $name, $directory, $model);
        }

        $data['sort_order'] = isset($validated['sort_order']) ? (int) $validated['sort_order'] : null;

        return $this->prepare($data, $model);
    }

    private function handleUpload(Request $request, string $name, string $directory, ?Model $model): ?string
    {
        $current = $model?->{$name};

        if ($request->file($name) instanceof UploadedFile) {
            Uploads::delete($current);

            return Uploads::store($request->file($name), $directory);
        }

        if ($request->boolean('remove_'.$name)) {
            Uploads::delete($current);

            return null;
        }

        return $current;
    }

    /**
     * @return array<int, string>
     */
    private function lines(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))->map(fn (string $line) => trim($line))->filter()->values()->all();
    }

    private function message(string $masculine, string $feminine): string
    {
        $labels = $this->labels();

        return Str::ucfirst($labels['singular']).' '.($labels['feminine'] ? $feminine : $masculine).' com sucesso.';
    }
}
