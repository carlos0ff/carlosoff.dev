<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Services\Analytics;
use App\Services\PortfolioContent;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const PER_PAGE = 15;

    private const SORTS = ['title', 'updated_at', 'sort_order'];

    public function index(Request $request, Analytics $analytics): View
    {
        $status = in_array($request->query('status'), ['published', 'draft', 'featured', 'trashed'], true) ? $request->query('status') : 'all';
        $search = trim((string) $request->query('q'));
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : null;
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $projects = Project::query()
            ->when($status === 'published', fn (Builder $q) => $q->where('is_active', true))
            ->when($status === 'draft', fn (Builder $q) => $q->where('is_active', false))
            ->when($status === 'featured', fn (Builder $q) => $q->where('featured', true))
            ->when($status === 'trashed', fn (Builder $q) => $q->onlyTrashed())
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($sort, fn (Builder $q) => $q->orderBy($sort, $direction)->orderBy('id'), fn (Builder $q) => $q->ordered())
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'views' => $analytics->viewsByProject($projects->pluck('id')->all()),
            'filters' => compact('status', 'search', 'sort', 'direction'),
            'canReorder' => $sort === null && $status !== 'trashed' && $search === '',
            'counts' => [
                'all' => Project::count(),
                'published' => Project::where('is_active', true)->count(),
                'draft' => Project::where('is_active', false)->count(),
                'featured' => Project::where('featured', true)->count(),
                'trashed' => Project::onlyTrashed()->count(),
            ],
            'featuredLimit' => (int) app(PortfolioContent::class)->setting('featured_projects_limit'),
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.form', [
            'project' => new Project(['technologies' => [], 'is_active' => true, 'featured' => false]),
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $data = $this->attributesFrom($request, null);
        $data['sort_order'] ??= (int) Project::withTrashed()->max('sort_order') + 1;

        $project = Project::create($data);

        AuditLog::record(AuditAction::Create, 'Projeto', $project->id, $project->title);

        return redirect()->route('admin.projects.index')->with('status', 'Projeto criado com sucesso.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.form', ['project' => $project]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $wasPublished = $project->is_active;
        $wasFeatured = $project->featured;

        $data = $this->attributesFrom($request, $project);
        $data['sort_order'] ??= $project->sort_order;

        $project->update($data);

        AuditLog::record(AuditAction::Update, 'Projeto', $project->id, $project->title);
        $this->recordFlagChanges($project, $wasPublished, $wasFeatured);

        return redirect()->route('admin.projects.index')->with('status', 'Projeto atualizado com sucesso.');
    }

    /**
     * Página pública do projeto, inclusive rascunhos (só para quem está no painel).
     */
    public function preview(Project $project): View|RedirectResponse
    {
        if (! $project->hasDetailPage()) {
            return back()->withErrors(['preview' => 'Este projeto ainda não tem conteúdo detalhado (visão geral, funcionalidades...) para exibir uma página.']);
        }

        return view('pages.project', [
            'project' => $project,
            'related' => $project->relatedProjects(),
            'isPreview' => true,
        ]);
    }

    /** Exclusão lógica: vai para a lixeira e pode ser restaurado; imagens enviadas são mantidas até lá. */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        AuditLog::record(AuditAction::Delete, 'Projeto', $project->id, $project->title);

        return redirect()->route('admin.projects.index')->with('status', 'Projeto excluído. Você pode restaurá-lo na lixeira.');
    }

    public function restore(int $id): RedirectResponse
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();

        AuditLog::record(AuditAction::Restore, 'Projeto', $project->id, $project->title);

        return redirect()->route('admin.projects.index', ['status' => 'trashed'])->with('status', 'Projeto restaurado com sucesso.');
    }

    public function toggle(Project $project): RedirectResponse
    {
        $project->update(['is_active' => ! $project->is_active]);

        AuditLog::record($project->is_active ? AuditAction::Publish : AuditAction::Unpublish, 'Projeto', $project->id, $project->title);

        return back()->with('status', $project->is_active ? 'Projeto publicado.' : 'Projeto movido para rascunho.');
    }

    public function feature(Project $project): RedirectResponse
    {
        $limit = (int) app(PortfolioContent::class)->setting('featured_projects_limit');

        if (! $project->featured && $limit > 0 && Project::where('featured', true)->count() >= $limit) {
            return back()->withErrors(['featured' => "Limite de {$limit} projetos em destaque atingido. Remova o destaque de outro projeto ou aumente o limite em Configurações."]);
        }

        $project->update(['featured' => ! $project->featured]);

        AuditLog::record($project->featured ? AuditAction::Feature : AuditAction::Unfeature, 'Projeto', $project->id, $project->title);

        return back()->with('status', $project->featured ? 'Projeto colocado em destaque.' : 'Destaque removido.');
    }

    /**
     * Troca a posição do projeto com o vizinho (dentro do mesmo grupo de destaque, já que
     * destaques vêm primeiro na ordem pública) e renumera tudo de 1..n, sem valores negativos.
     */
    public function move(Request $request, Project $project): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($project, $direction): void {
            $items = Project::ordered()->get()->all();
            $position = array_search($project->id, array_column($items, 'id'), true);
            $neighborPosition = $direction === 'up' ? $position - 1 : $position + 1;

            if (isset($items[$neighborPosition]) && $items[$neighborPosition]->featured === $project->featured) {
                [$items[$position], $items[$neighborPosition]] = [$items[$neighborPosition], $items[$position]];
            }

            foreach ($items as $index => $item) {
                if ($item->sort_order !== $index + 1) {
                    $item->update(['sort_order' => $index + 1]);
                }
            }
        });

        AuditLog::record(AuditAction::Update, 'Projeto', $project->id, $project->title, 'Reordenou');

        return back();
    }

    private function recordFlagChanges(Project $project, bool $wasPublished, bool $wasFeatured): void
    {
        if ($wasPublished !== $project->is_active) {
            AuditLog::record($project->is_active ? AuditAction::Publish : AuditAction::Unpublish, 'Projeto', $project->id, $project->title);
        }

        if ($wasFeatured !== $project->featured) {
            AuditLog::record($project->featured ? AuditAction::Feature : AuditAction::Unfeature, 'Projeto', $project->id, $project->title);
        }
    }

    /**
     * Converte o formulário nas colunas do projeto e no JSON `details` que alimenta a página pública.
     *
     * @return array<string, mixed>
     */
    private function attributesFrom(ProjectRequest $request, ?Project $project): array
    {
        $validated = $request->validated();

        $data = collect($validated)->only([
            'title', 'acronym', 'description', 'github_url', 'demo_url', 'project_date',
            'seo_title', 'seo_description', 'og_title', 'og_description', 'featured', 'is_active',
        ])->map(fn ($value) => $value === '' ? null : $value)->all();

        if (filled($validated['slug'] ?? null)) {
            $data['slug'] = $validated['slug'];
        }

        $data['technologies'] = array_values($validated['technologies'] ?? []);
        $data['sort_order'] = isset($validated['sort_order']) ? (int) $validated['sort_order'] : null;
        $data['image'] = $this->mainImage($request, $project);
        $data['og_image'] = $this->ogImage($request, $project);
        $data['details'] = $this->details($request, $project);

        return $data;
    }

    private function mainImage(ProjectRequest $request, ?Project $project): ?string
    {
        $current = $project?->image;

        if ($request->file('image') instanceof UploadedFile) {
            $this->deleteMainImage($project);

            return $request->file('image')->store(Project::UPLOAD_DIRECTORY, 'public');
        }

        if ($request->boolean('remove_image')) {
            $this->deleteMainImage($project);

            return null;
        }

        return $current;
    }

    /** Só apaga arquivos enviados pelo admin; imagens estáticas de `public/image/...` nunca são tocadas. */
    private function deleteMainImage(?Project $project): void
    {
        if ($project?->hasUploadedImage()) {
            Storage::disk('public')->delete($project->image);
        }
    }

    private function ogImage(ProjectRequest $request, ?Project $project): ?string
    {
        $current = $project?->og_image;

        if ($request->file('og_image') instanceof UploadedFile) {
            Uploads::delete($current);

            return Uploads::store($request->file('og_image'), 'projects/og');
        }

        if ($request->boolean('remove_og_image')) {
            Uploads::delete($current);

            return null;
        }

        return $current;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function details(ProjectRequest $request, ?Project $project): ?array
    {
        $validated = $request->validated();
        $paragraphs = fn (?string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $text))));
        $lines = fn (?string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));

        $challenges = collect($validated['challenges'] ?? [])
            ->map(fn (array $challenge) => [
                'title' => trim((string) ($challenge['title'] ?? '')),
                'problem' => trim((string) ($challenge['problem'] ?? '')),
                'solution' => trim((string) ($challenge['solution'] ?? '')),
            ])
            ->filter(fn (array $challenge) => $challenge['title'] !== '')
            ->values()
            ->all();

        $details = [
            'overview' => $paragraphs($validated['overview'] ?? null),
            'objective' => trim((string) ($validated['objective'] ?? '')),
            'features' => $lines($validated['features'] ?? null),
            'architecture' => trim((string) ($validated['architecture'] ?? '')),
            'sections' => blank($validated['extra_sections'] ?? null) ? [] : json_decode($validated['extra_sections'], true),
            'development' => $paragraphs($validated['development'] ?? null),
            'challenges' => $challenges,
            'conclusion' => trim((string) ($validated['conclusion'] ?? '')),
            'screenshots' => $this->screenshots($request, $project),
        ];

        $details = array_filter($details, fn ($value) => filled($value));

        return $details === [] ? null : $details;
    }

    /**
     * @return array<int, array{src: string, alt: string, caption: string}>
     */
    private function screenshots(ProjectRequest $request, ?Project $project): array
    {
        $existing = collect($project?->details['screenshots'] ?? [])->keyBy('src');
        $kept = [];

        foreach ($request->validated()['screenshots'] ?? [] as $row) {
            $src = $row['src'];

            // Só aceita linhas que já pertencem ao projeto: o formulário não pode injetar caminhos novos.
            if (! $existing->has($src)) {
                continue;
            }

            if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                Uploads::delete($src);

                continue;
            }

            $kept[] = ['src' => $src, 'alt' => trim((string) ($row['alt'] ?? '')), 'caption' => trim((string) ($row['caption'] ?? ''))];
        }

        foreach ($request->file('gallery_files', []) as $file) {
            $kept[] = [
                'src' => Uploads::store($file, 'projects/gallery'),
                'alt' => 'Screenshot do projeto '.$request->input('title'),
                'caption' => '',
            ];
        }

        return $kept;
    }
}
