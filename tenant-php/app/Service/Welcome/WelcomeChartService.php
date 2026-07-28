<?php

declare(strict_types=1);

namespace App\Service\Welcome;

use App\Service\Dashboard\DashboardAggregate;
use Carbon\Carbon;

final class WelcomeChartService
{
    public function __construct(
        private readonly DashboardAggregate $aggregate,
    ) {}

    public function series(string $type, string $start, string $end): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->startOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $dates = $this->aggregate->dateRange($from, $to);
        // visits = 登录成功次数；visitors = 当日独立用户名数（近似）
        $visitsMap = $this->aggregate->countByDate(
            'user_login_log',
            'login_time',
            $from,
            $to,
            static fn ($q) => $q->where('status', 1)
        );

        $visitorsMap = [];
        try {
            $rows = \Hyperf\DbConnection\Db::table('user_login_log')
                ->where('status', 1)
                ->where('login_time', '>=', $from->copy()->startOfDay()->toDateTimeString())
                ->where('login_time', '<=', $to->copy()->endOfDay()->toDateTimeString())
                ->selectRaw('DATE(login_time) as d, COUNT(DISTINCT username) as c')
                ->groupBy('d')
                ->get();
            foreach ($rows as $row) {
                $visitorsMap[(string) $row->d] = (int) $row->c;
            }
        } catch (\Throwable) {
        }

        return [
            'dates' => $dates,
            'visits' => $this->aggregate->fillDaily($visitsMap, $dates),
            'visitors' => $this->aggregate->fillDaily($visitorsMap, $dates),
            'type' => $type,
        ];
    }
}
