<?php

namespace App\Services\GitHub;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubContributionService
{
    private const SQUARE = 18;

    private const GAP = 5;

    private const STEP = self::SQUARE + self::GAP;

    private const TOP_OFFSET = 34;

    private const MAX_YEARS = 5;

    /** Espaço uniforme entre quadrados, na mesma unidade do viewBox (≈1,5px na tela). */
    private const CELL_GAP = 2.1;

    /**
     * GitHub's own official dark-theme contribution colors, keyed by contributionLevel.
     * The GraphQL `color` field always returns the LIGHT-theme hex (verified live:
     * #ebedf0/#40c463/#216e39...), which clashes with this portfolio's dark card.
     * We still fetch `color` for transparency/debugging, but render using this
     * dark-theme equivalent so the same contributionLevel always maps to the same
     * relative intensity GitHub itself would show in dark mode.
     */
    private const LEVEL_COLORS = [
        0 => '#161b22',
        1 => '#0e4429',
        2 => '#006d32',
        3 => '#26a641',
        4 => '#39d353',
    ];

    private const CONTRIBUTION_LEVELS = [
        'NONE' => 0,
        'FIRST_QUARTILE' => 1,
        'SECOND_QUARTILE' => 2,
        'THIRD_QUARTILE' => 3,
        'FOURTH_QUARTILE' => 4,
    ];

    /**
     * Build the full contribution graph payload for a given login/year.
     *
     * @param  string  $login  GitHub username
     * @param  string|int  $year  'current' for the rolling last-365-days window, or a calendar year (e.g. 2024)
     */
    public function getContributionGraph(string $login, string|int $year = 'current'): array
    {
        $cacheKey = "github-contributions:v2:{$login}:{$year}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($login, $year) {
            return $this->fetch($login, $year);
        });
    }

    private function fetch(string $login, string|int $year): array
    {
        $isCurrent = $year === 'current';

        if ($isCurrent) {
            $to = Carbon::now();
            $from = $to->copy()->subDays(364)->startOfDay();
        } else {
            $from = Carbon::createFromDate((int) $year, 1, 1)->startOfDay();
            $to = Carbon::createFromDate((int) $year, 12, 31)->endOfDay();
        }

        $query = <<<'GRAPHQL'
            query ($login: String!, $from: DateTime!, $to: DateTime!) {
                user(login: $login) {
                    createdAt
                    contributionsCollection(from: $from, to: $to) {
                        contributionCalendar {
                            totalContributions
                            weeks {
                                contributionDays {
                                    date
                                    weekday
                                    contributionCount
                                    contributionLevel
                                    color
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;

        $response = Http::withToken(config('services.github.token'))->acceptJson()
            ->post('https://api.github.com/graphql', [
                'query' => $query,
                'variables' => [
                    'login' => $login,
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ],
            ]);

        if ($response->failed() || isset($response->json()['errors'])) {
            Log::warning('GitHub contribution graph request failed', [
                'login' => $login,
                'year' => $year,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->empty($login, $year);
        }

        $user = $response->json('data.user');

        if (! $user) {
            Log::warning('GitHub user not found for contribution graph', ['login' => $login]);

            return $this->empty($login, $year);
        }

        $calendar = $user['contributionsCollection']['contributionCalendar'];
        $creationYear = (int) Carbon::parse($user['createdAt'])->format('Y');

        return $this->buildLayout($calendar, $creationYear, $year);
    }

    private function buildLayout(array $calendar, int $creationYear, string|int $selectedYear): array
    {
        $squares = [];
        $months = [];
        $lastMonth = null;

        // Área externa do grid (não muda) e quadrado/gap calculados juntos para caber nela:
        // colunas × cellSize + (colunas - 1) × gap ≤ largura, e o mesmo para as 7 linhas.
        $weekCount = max(count($calendar['weeks']), 1);
        $width = ($weekCount - 1) * self::STEP + self::SQUARE;
        $height = self::TOP_OFFSET + 6 * self::STEP + self::SQUARE;
        $cellSize = floor(min(
            ($width - ($weekCount - 1) * self::CELL_GAP) / $weekCount,
            ($height - self::TOP_OFFSET - 6 * self::CELL_GAP) / 7,
        ) * 1000) / 1000;
        // $cellSize = maior quadrado tal que 53×cellSize + 52×gap ≤ largura e 7×cellSize + 6×gap ≤ altura.
        $gap = self::CELL_GAP;
        $startX = 0;
        $startY = self::TOP_OFFSET;

        foreach ($calendar['weeks'] as $weekIndex => $week) {
            $firstDay = $week['contributionDays'][0] ?? null;

            if ($firstDay) {
                $month = Carbon::parse($firstDay['date'])->format('M');

                if ($month !== $lastMonth) {
                    $months[] = [
                        'week' => $weekIndex,
                        'x' => $weekIndex * self::STEP,
                        'label' => $month,
                    ];
                    $lastMonth = $month;
                }
            }

            foreach ($week['contributionDays'] as $day) {
                $level = self::CONTRIBUTION_LEVELS[$day['contributionLevel']] ?? 0;

                $squares[] = [
                    'week' => $weekIndex,
                    'weekday' => $day['weekday'],
                    'x' => $startX + ($weekIndex * ($cellSize + $gap)),
                    'y' => $startY + ($day['weekday'] * ($cellSize + $gap)),
                    'size' => $cellSize,
                    'color' => self::LEVEL_COLORS[$level],
                    'level' => $level,
                    'count' => $day['contributionCount'],
                    'date' => $day['date'],
                ];
            }
        }

        return [
            'total' => $calendar['totalContributions'],
            'squares' => $squares,
            'months' => $months,
            'width' => $width,
            'height' => $height,
            'cellSize' => $cellSize,
            'gap' => self::CELL_GAP,
            'weekCount' => $weekCount,
            'topOffset' => self::TOP_OFFSET,
            'years' => $this->buildYearOptions($creationYear),
            'selectedYear' => $selectedYear,
            'isCurrent' => $selectedYear === 'current',
        ];
    }

    private function buildYearOptions(int $creationYear): array
    {
        $currentYear = (int) now()->format('Y');
        $oldestYear = max($creationYear, $currentYear - (self::MAX_YEARS - 1));

        $years = ['current'];

        for ($year = $currentYear - 1; $year >= $oldestYear; $year--) {
            $years[] = $year;
        }

        return $years;
    }

    private function empty(string $login, string|int $year): array
    {
        $currentYear = (int) now()->format('Y');

        return [
            'total' => 0,
            'squares' => [],
            'months' => [],
            'width' => 0,
            'height' => self::TOP_OFFSET + 6 * self::STEP + self::SQUARE,
            'cellSize' => 0,
            'gap' => 0,
            'weekCount' => 0,
            'topOffset' => self::TOP_OFFSET,
            'years' => ['current', $currentYear - 1, $currentYear - 2, $currentYear - 3, $currentYear - 4],
            'selectedYear' => $year,
            'isCurrent' => $year === 'current',
        ];
    }
}
