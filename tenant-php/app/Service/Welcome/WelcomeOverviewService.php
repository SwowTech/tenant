<?php
declare(strict_types=1);

namespace App\Service\Welcome;

use Carbon\Carbon;

final class WelcomeOverviewService
{
    public function __construct(
        private readonly WelcomeVersionService $version,
        private readonly SaasBindService $saasBind,
        private readonly SystemCheckService $systemCheck,
        private readonly WelcomeChartService $chart,
        private readonly WelcomeMarketService $market,
    ) {}

    public function get(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(6);
        $check = $this->systemCheck->run();
        return [
            'version' => $this->version->current(),
            'saas_bind' => $this->saasBind->status(),
            'check_summary' => [
                'check_num' => $check['check_num'],
                'check_wrong_num' => $check['check_wrong_num'],
                'report_text' => $check['report_text'],
            ],
            'chart' => $this->chart->series(
                'realtime',
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            ),
            'market_stats' => $this->market->stats(),
            'saas_admin_url' => rtrim((string) env('SAAS_ADMIN_URL', 'http://127.0.0.1:5174'), '/'),
        ];
    }
}
