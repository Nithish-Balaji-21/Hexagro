<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class FiscalYear
{
    public static function startMonth(): int
    {
        return (int) config('hexagro.fiscal_year_start_month', 4);
    }

    public static function startYear(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();
        $year = (int) $asOf->format('Y');
        $month = (int) $asOf->format('n');

        return $month >= self::startMonth() ? $year : $year - 1;
    }

    /**
     * @return list<array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    public static function months(?int $startYear = null): array
    {
        $startYear ??= self::startYear();
        $startMonth = self::startMonth();
        $months = [];

        for ($offset = 0; $offset < 12; $offset++) {
            $monthIndex = (($startMonth - 1 + $offset) % 12) + 1;
            $year = $startYear + intdiv($startMonth - 1 + $offset, 12);
            $start = Carbon::create($year, $monthIndex, 1)->startOfDay();

            $months[] = [
                'key' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
                'start' => $start->copy(),
                'end' => $start->copy()->endOfMonth(),
            ];
        }

        return $months;
    }

    public static function monthKey(CarbonInterface $date): string
    {
        return $date->format('Y-m');
    }

    public static function monthLabel(CarbonInterface $date): string
    {
        return $date->format('M Y');
    }
}
