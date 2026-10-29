<?php

namespace App\Services;

use App\Models\PageView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Estatísticas de acesso. Toda agregação é feita no banco (GROUP BY); o PHP só recebe
 * uma linha por dia/página/projeto, nunca as visualizações individuais.
 */
class Analytics
{
    /** @var array<string, string> */
    public const RANGES = [
        'today' => 'Hoje',
        '7d' => '7 dias',
        '30d' => '30 dias',
        '90d' => '90 dias',
        '12m' => '12 meses',
        'all' => 'Total',
    ];

    /** @var array<string, string> */
    public const GRANULARITIES = ['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'];

    public static function normalizeRange(?string $range, string $default = '30d'): string
    {
        return array_key_exists((string) $range, self::RANGES) ? (string) $range : $default;
    }

    public static function normalizeGranularity(?string $granularity): string
    {
        return array_key_exists((string) $granularity, self::GRANULARITIES) ? (string) $granularity : 'day';
    }

    /** Início do período (`null` = desde sempre). "7 dias" inclui hoje: hoje + 6 dias anteriores. */
    public static function since(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '90d' => now()->subDays(89)->startOfDay(),
            '12m' => now()->subMonthsNoOverflow(11)->startOfMonth(),
            default => null,
        };
    }

    /**
     * @return Builder<PageView>
     */
    private function inRange(string $range): Builder
    {
        return PageView::query()->when(self::since($range), fn (Builder $query, Carbon $since) => $query->where('page_views.created_at', '>=', $since));
    }

    /**
     * @return array{today: int, week: int, month: int, total: int, unique_today: int}
     */
    public function summary(): array
    {
        $row = PageView::query()->selectRaw(
            'COUNT(*) as total,
             SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as today,
             SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as week,
             SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as month',
            [self::since('today'), self::since('7d'), self::since('30d')],
        )->first();

        return [
            'today' => (int) $row->today,
            'week' => (int) $row->week,
            'month' => (int) $row->month,
            'total' => (int) $row->total,
            'unique_today' => (int) PageView::where('created_at', '>=', self::since('today'))->distinct()->count('ip_hash'),
        ];
    }

    public function totalInRange(string $range): int
    {
        return $this->inRange($range)->count();
    }

    /**
     * @return Collection<int, object{path: string, views: int}>
     */
    public function topPages(string $range, int $limit = 10): Collection
    {
        return $this->inRange($range)
            ->select('path', DB::raw('COUNT(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->orderBy('path')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object{project_id: int, views: int, title: string, slug: string}>
     */
    public function topProjects(string $range, int $limit = 10): Collection
    {
        return $this->inRange($range)
            ->whereNotNull('project_id')
            ->join('projects', 'projects.id', '=', 'page_views.project_id')
            ->select('page_views.project_id', 'projects.title', 'projects.slug', DB::raw('COUNT(*) as views'))
            ->groupBy('page_views.project_id', 'projects.title', 'projects.slug')
            ->orderByDesc('views')
            ->orderBy('projects.title')
            ->limit($limit)
            ->get();
    }

    /**
     * Distribuição por dispositivo, navegador, sistema operacional ou origem (domínio de referência).
     *
     * @param  'device'|'browser'|'os'|'referer_host'  $column
     * @return Collection<int, object{label: string, views: int}>
     */
    public function breakdown(string $column, string $range, int $limit = 6): Collection
    {
        abort_unless(in_array($column, ['device', 'browser', 'os', 'referer_host'], true), 404);

        return $this->inRange($range)
            ->select(DB::raw("COALESCE({$column}, 'Direto / desconhecido') as label"), DB::raw('COUNT(*) as views'))
            ->groupBy('label')
            ->orderByDesc('views')
            ->orderBy('label')
            ->limit($limit)
            ->get();
    }

    /**
     * Total de visualizações por projeto (para a listagem do painel), em uma única consulta.
     *
     * @param  array<int, int>  $projectIds
     * @return array<int, int>
     */
    public function viewsByProject(array $projectIds): array
    {
        return PageView::query()
            ->whereIn('project_id', $projectIds)
            ->select('project_id', DB::raw('COUNT(*) as views'))
            ->groupBy('project_id')
            ->pluck('views', 'project_id')
            ->map(fn ($views) => (int) $views)
            ->all();
    }

    /**
     * Série temporal agrupada por dia (no banco) e consolidada em dia/semana/mês (no PHP, sobre poucas linhas),
     * com zeros nos períodos sem acesso.
     *
     * @return array{labels: array<int, string>, values: array<int, int>, total: int}
     */
    public function series(string $range, string $granularity): array
    {
        $start = self::since($range) ?? PageView::min('created_at');
        $start = $start ? Carbon::parse($start)->startOfDay() : now()->startOfDay();

        $perDay = PageView::query()
            ->where('created_at', '>=', $start)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as views'))
            ->groupBy('day')
            ->pluck('views', 'day');

        $buckets = [];
        for ($cursor = $start->copy(); $cursor->lte(now()); $cursor->addDay()) {
            $key = $this->bucketKey($cursor, $granularity);
            $buckets[$key] ??= ['label' => $this->bucketLabel($cursor, $granularity), 'views' => 0];
            $buckets[$key]['views'] += (int) ($perDay[$cursor->toDateString()] ?? 0);
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'values' => array_column($buckets, 'views'),
            'total' => array_sum(array_column($buckets, 'views')),
        ];
    }

    private function bucketKey(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'week' => $date->copy()->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    private function bucketLabel(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'week' => $date->copy()->startOfWeek()->format('d/m'),
            'month' => $date->format('m/Y'),
            default => $date->format('d/m'),
        };
    }
}
