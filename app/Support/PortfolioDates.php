<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class PortfolioDates
{
    /** @var array<int, string> */
    private const MONTHS = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    /** "Set 2026" */
    public static function monthYear(?CarbonInterface $date): ?string
    {
        return $date ? self::MONTHS[$date->month].' '.$date->year : null;
    }

    /** "Set 2026 – Presente", "Fev 2023 – Dez 2025 · Trancada". */
    public static function period(?CarbonInterface $start, ?CarbonInterface $end, bool $current, ?string $note = null): ?string
    {
        $from = self::monthYear($start);
        // Data final informada sempre vence: "em andamento" com previsão de término (ex.: Dez 2027) mantém a previsão.
        $to = self::monthYear($end) ?? ($current ? 'Presente' : null);

        $period = match (true) {
            $from !== null && $to !== null => $from.' – '.$to,
            $from !== null => $from,
            default => $to,
        };

        if ($period !== null && filled($note)) {
            $period .= ' · '.$note;
        }

        return $period;
    }

    /** Converte "Set 2026" / "Jun 2021" em uma data (dia 1). Usado só na importação do conteúdo antigo. */
    public static function parseMonthYear(string $text): ?Carbon
    {
        if (! preg_match('/^([[:alpha:]]{3})\.?\s+(\d{4})$/u', trim($text), $match)) {
            return null;
        }

        $month = array_search(mb_convert_case($match[1], MB_CASE_TITLE), self::MONTHS, true);

        return $month ? Carbon::create((int) $match[2], $month, 1)->startOfDay() : null;
    }
}
