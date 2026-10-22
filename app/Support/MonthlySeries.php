<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Month-by-month totals for charts, e.g. revenue for the last 12 months.
 * Grouped in PHP so it works the same on MySQL, MariaDB and SQLite.
 */
class MonthlySeries
{
    /** @return list<array{label: string, key: string, value: float}> */
    public static function sum(Builder $query, string $dateColumn, string $valueColumn, int $months): array
    {
        return self::build($query, $dateColumn, $months, fn ($rows) => (float) $rows->sum($valueColumn), [$dateColumn, $valueColumn]);
    }

    /** @return list<array{label: string, key: string, value: float}> */
    public static function count(Builder $query, string $dateColumn, int $months): array
    {
        return self::build($query, $dateColumn, $months, fn ($rows) => (float) $rows->count(), [$dateColumn]);
    }

    protected static function build(Builder $query, string $dateColumn, int $months, callable $reduce, array $columns): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $byMonth = (clone $query)
            ->where($dateColumn, '>=', $start)
            ->get($columns)
            ->groupBy(fn ($row) => Carbon::parse($row->{$dateColumn})->format('Y-m'));

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $series[] = ['label' => $month->format('M'), 'key' => $key, 'value' => $reduce($byMonth->get($key, collect()))];
        }

        return $series;
    }
}
