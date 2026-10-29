<?php

namespace App\Models;

use App\Services\PortfolioContent;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'description',
    'image',
    'technologies',
    'details',
    'github_url',
    'demo_url',
    'featured',
    'is_active',
    'sort_order',
    'acronym',
    'project_date',
    'seo_title',
    'seo_description',
    'og_title',
    'og_description',
    'og_image',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /** Pasta (no disco `public`) onde ficam as imagens enviadas pelo painel admin. */
    public const UPLOAD_DIRECTORY = 'projects';

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            $project->slug = filled($project->slug) ? $project->slug : static::uniqueSlug($project->title);
        });

        $flush = fn () => PortfolioContent::flush();
        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    private static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'projeto';
        $slug = $base;

        for ($suffix = 2; static::withTrashed()->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'details' => 'array',
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'project_date' => 'date',
        ];
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * URL pública da imagem: arquivos estáticos em `public/image/...` usam `asset()`;
     * uploads do painel ficam no disco `public`.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->image) {
                return null;
            }

            return $this->hasUploadedImage()
                ? Storage::disk('public')->url($this->image)
                : asset($this->image);
        });
    }

    /**
     * Projetos ativos mais parecidos com este, ordenados pelo número de tecnologias em comum.
     * Empates mantêm a ordenação padrão (destaque, ordem, id), o que também completa a lista
     * com outros projetos quando não há tecnologias suficientes em comum.
     *
     * @return Collection<int, Project>
     */
    public function relatedProjects(int $limit = 3): Collection
    {
        $current = collect($this->technologies ?? [])
            ->map(fn (string $technology): string => mb_strtolower($technology));

        return static::published()
            ->whereKeyNot($this->getKey())
            ->ordered()
            ->get()
            ->sortByDesc(fn (Project $project): int => collect($project->technologies ?? [])
                ->map(fn (string $technology): string => mb_strtolower($technology))
                ->intersect($current)
                ->count())
            ->take($limit)
            ->values();
    }

    /** Só projetos com conteúdo em `details` têm página própria em /projetos/{slug}. */
    public function hasDetailPage(): bool
    {
        return ! empty($this->details);
    }

    /** Somente imagens enviadas pelo admin podem ser apagadas do disco. */
    public function hasUploadedImage(): bool
    {
        return $this->image !== null && Str::startsWith($this->image, self::UPLOAD_DIRECTORY.'/');
    }
}
