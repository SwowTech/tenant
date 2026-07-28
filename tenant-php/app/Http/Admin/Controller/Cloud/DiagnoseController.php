<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Cloud;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Cloud\CloudDiagnoseService;
use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Swagger\Annotation\HyperfServer;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
#[Controller(prefix: '/admin/cloud')]
final class DiagnoseController extends AbstractController
{
    public function __construct(
        private readonly CloudDiagnoseService $svc,
        private readonly CloudSiteSettingService $site,
    ) {}

    #[GetMapping(path: 'diagnose')]
    public function index(): Result
    {
        return $this->success($this->svc->diagnose());
    }

    #[GetMapping(path: 'diagnose/token')]
    public function token(): Result
    {
        if (! $this->site->isBound()) {
            return $this->error('未注册云站点');
        }

        return $this->success(['token' => $this->site->getTokenPlain()]);
    }

    #[PostMapping(path: 'diagnose/reset')]
    public function reset(): Result
    {
        try {
            return $this->success($this->svc->reset());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    #[GetMapping(path: 'diagnose/ping')]
    public function ping(): Result
    {
        return $this->success($this->svc->pingSaas());
    }
}
