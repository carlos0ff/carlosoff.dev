<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\SiteContent;
use App\Models\Technology;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Leitura do conteúdo público do portfólio a partir do banco (gerenciado no painel /admin).
 * Devolve arrays no mesmo formato que os componentes Blade já consumiam, com cache que é
 * invalidado por `flush()` sempre que o conteúdo é salvo, excluído ou restaurado.
 */
class PortfolioContent
{
    private const CACHE_KEYS = ['experiences', 'education', 'courses', 'stack', 'about', 'settings'];

    public static function flush(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget(self::cacheKey($key));
        }
    }

    private static function cacheKey(string $key): string
    {
        return 'portfolio.content.'.$key;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function experiences(): array
    {
        return Cache::rememberForever(self::cacheKey('experiences'), fn () => Experience::published()->ordered()->get()->map->toPublicArray()->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function education(): array
    {
        return Cache::rememberForever(self::cacheKey('education'), fn () => Education::published()->ordered()->get()->map->toPublicArray()->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function courses(): array
    {
        return Cache::rememberForever(self::cacheKey('courses'), fn () => Course::published()->ordered()->get()->map->toPublicArray()->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function stack(): array
    {
        return Cache::rememberForever(self::cacheKey('stack'), fn () => Technology::published()->ordered()->get()->map->toPublicArray()->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function about(): array
    {
        return Cache::rememberForever(self::cacheKey('about'), function (): array {
            $about = array_merge(self::aboutDefaults(), SiteContent::read(SiteContent::ABOUT));
            $about['paragraphs'] = collect(preg_split('/\R{2,}/', trim((string) ($about['biography'] ?? ''))))
                ->map(fn (string $paragraph) => trim($paragraph))
                ->filter()
                ->values()
                ->all();

            return $about;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return Cache::rememberForever(self::cacheKey('settings'), fn () => array_merge(self::settingsDefaults(), SiteContent::read(SiteContent::SETTINGS)));
    }

    public function setting(string $key): mixed
    {
        return $this->settings()[$key] ?? null;
    }

    public function projectsPerPage(): int
    {
        return max(1, (int) $this->setting('projects_per_page'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function aboutDefaults(): array
    {
        return [
            'name' => '',
            'headline' => '',
            'subtitle' => null,
            'description' => null,
            'biography' => '',
            'quote' => null,
            'photo' => null,
            'resume' => null,
            'email' => null,
            'location' => null,
            'professional_status' => null,
            'links' => [],
            'seo_title' => null,
            'seo_description' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function settingsDefaults(): array
    {
        return [
            'projects_per_page' => (int) config('portfolio.projects_per_page', 4),
            'featured_projects_limit' => 0, // 0 = sem limite de projetos em destaque
            'track_page_views' => true,
        ];
    }

    /** Rótulo curto para SEO/OG: primeiro parágrafo da biografia. */
    public static function excerpt(string $text, int $limit = 160): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $text)), $limit);
    }
}
