<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Throwable;

final class DashboardAggregate
{
    /**
     * @return list<string>
     */
    public function dateRange(Carbon $from, Carbon $to): array
    {
        $dates = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * @param  array<string, int>  $map
     * @param  list<string>  $dates
     * @return list<int>
     */
    public function fillDaily(array $map, array $dates): array
    {
        $out = [];
        foreach ($dates as $date) {
            $out[] = (int) ($map[$date] ?? 0);
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function countByDate(string $table, string $timeColumn, Carbon $from, Carbon $to, ?callable $query = null): array
    {
        try {
            $builder = Db::table($table)
                ->where($timeColumn, '>=', $from->copy()->startOfDay()->toDateTimeString())
                ->where($timeColumn, '<=', $to->copy()->endOfDay()->toDateTimeString());
            if ($query !== null) {
                $query($builder);
            }
            $rows = $builder
                ->selectRaw('DATE(' . $timeColumn . ') as d, COUNT(*) as c')
                ->groupBy('d')
                ->get();
            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row->d] = (int) $row->c;
            }

            return $map;
        } catch (Throwable) {
            return [];
        }
    }

    public function countBetween(string $table, string $timeColumn, Carbon $from, Carbon $to, ?callable $query = null): int
    {
        try {
            $builder = Db::table($table)
                ->where($timeColumn, '>=', $from->copy()->startOfDay()->toDateTimeString())
                ->where($timeColumn, '<=', $to->copy()->endOfDay()->toDateTimeString());
            if ($query !== null) {
                $query($builder);
            }

            return (int) $builder->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function tableCount(string $table): int
    {
        try {
            return (int) Db::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public function growthPercent(int $current, int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @return list<array{name: string, value: int}>
     */
    public function topGrouped(string $table, string $column, Carbon $from, Carbon $to, string $timeColumn, int $limit = 10, ?callable $query = null): array
    {
        try {
            $builder = Db::table($table)
                ->where($timeColumn, '>=', $from->copy()->startOfDay()->toDateTimeString())
                ->where($timeColumn, '<=', $to->copy()->endOfDay()->toDateTimeString())
                ->whereNotNull($column)
                ->where($column, '!=', '');
            if ($query !== null) {
                $query($builder);
            }
            $rows = $builder
                ->selectRaw($column . ' as name, COUNT(*) as value')
                ->groupBy($column)
                ->orderByDesc('value')
                ->limit($limit)
                ->get();

            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'name' => (string) $row->name,
                    'value' => (int) $row->value,
                ];
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }
}
