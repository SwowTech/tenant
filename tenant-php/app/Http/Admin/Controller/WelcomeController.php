<?php
declare(strict_types=1);

namespace App\Http\Admin\Controller;

use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Welcome\SaasBindService;
use App\Service\Welcome\SystemCheckService;
use App\Service\Welcome\WelcomeChartService;
use App\Service\Welcome\WelcomeMarketService;
use App\Service\Welcome\WelcomeOverviewService;
use App\Service\Welcome\WelcomeVersionService;
use App\Service\App\TenantInstalledAppQueryService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class WelcomeController extends AbstractController
{
    public function __construct(
        private readonly WelcomeOverviewService $overview,
        private readonly WelcomeChartService $chart,
        private readonly WelcomeVersionService $version,
        private readonly SaasBindService $saasBind,
        private readonly SystemCheckService $systemCheck,
        private readonly WelcomeMarketService $market,
        private readonly TenantInstalledAppQueryService $installedApps,
    ) {}

    #[Get(path: '/admin/welcome/overview', operationId: 'welcomeOverview', summary: '欢迎页聚合', security: [['Bearer' => []]], tags: ['欢迎页'])]
    #[ResultResponse(new Result())]
    public function overview(): Result
    {
        return $this->success($this->overview->get());
    }

    #[Get(path: '/admin/welcome/chart', operationId: 'welcomeChart', summary: '欢迎页统计图', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function chart(RequestInterface $request): Result
    {
        $type = (string) $request->input('type', 'realtime');
        $start = (string) $request->input('start', date('Y-m-d', strtotime('-6 days')));
        $end = (string) $request->input('end', date('Y-m-d'));
        return $this->success($this->chart->series($type, $start, $end));
    }

    #[Get(path: '/admin/welcome/version/check', operationId: 'welcomeVersionCheck', summary: '检查新版本', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function versionCheck(): Result
    {
        return $this->success($this->version->check());
    }

    #[Get(path: '/admin/welcome/saas-bind', operationId: 'welcomeSaasBind', summary: 'SaaS 绑定状态', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function saasBind(): Result
    {
        return $this->success($this->saasBind->status());
    }

    #[Get(path: '/admin/welcome/system-check', operationId: 'welcomeSystemCheck', summary: '系统常规检测', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function systemCheck(): Result
    {
        return $this->success($this->systemCheck->run());
    }

    #[Get(path: '/admin/welcome/market/apps', operationId: 'welcomeMarketApps', summary: '市场应用列表', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function marketApps(RequestInterface $request): Result
    {
        return $this->success($this->market->apps($request->all()));
    }

    #[Get(path: '/admin/welcome/market/stats', operationId: 'welcomeMarketStats', summary: '市场统计', security: [['Bearer' => []]], tags: ['欢迎页'])]
    public function marketStats(): Result
    {
        return $this->success($this->market->stats());
    }

    #[Get(path: '/admin/welcome/installed-apps', operationId: 'welcomeInstalledApps', summary: '当前租户已安装应用（含停用）', security: [['Bearer' => []]], tags: ['欢迎页'])]
    #[ResultResponse(new Result())]
    public function installedApps(): Result
    {
        $list = $this->installedApps->listEnabled();

        return $this->success([
            'list' => $list,
            'groups' => $this->installedApps->listGroupedByFamily($list),
        ]);
    }
}
