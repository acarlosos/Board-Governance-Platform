<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

enum DashboardMetricsPeriod: string
{
    case AllTime = 'all_time';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';

    public function applyToCreatedAt(Builder $builder): void
    {
        match ($this) {
            self::AllTime => null,
            self::Last30Days => $builder->where(
                $builder->qualifyColumn('created_at'),
                '>=',
                now()->subDays(30),
            ),
            self::ThisMonth => $builder->where(
                $builder->qualifyColumn('created_at'),
                '>=',
                now()->startOfMonth(),
            ),
        };
    }

    /** @return list<self> */
    public static function filterOptions(): array
    {
        return [self::ThisMonth, self::Last30Days, self::AllTime];
    }
}
