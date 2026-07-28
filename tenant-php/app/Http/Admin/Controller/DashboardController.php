<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller;

use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Dashboard\DashboardAnalysisService;
use App\Service\Dashboard\DashboardReportService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardAnalysisService $analysis,
        private readonly DashboardReportService $report,
    ) {}

    #[Get(path: '/admin/dashboard/analysis', operationId: 'dashboardAnalysis', summary: '分析页真实数据', security: [['Bearer' => []]], tags: ['仪表盘'])]
    #[ResultResponse(new Result())]
    public function analysis(): Result
    {
        return $this->success($this->analysis->get());
    }

    #[Get(path: '/admin/dashboard/report', operationId: 'dashboardReport', summary: '统计报表真实数据', security: [['Bearer' => []]], tags: ['仪表盘'])]
    #[ResultResponse(new Result())]
    public function report(): Result
    {
        return $this->success($this->report->get());
    }
}
