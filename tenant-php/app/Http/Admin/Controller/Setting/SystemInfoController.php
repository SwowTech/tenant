<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Setting;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Setting\SystemInfoService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SystemInfoController extends AbstractController
{
    public function __construct(private readonly SystemInfoService $service) {}

    #[Get(
        path: '/admin/setting/systeminfo',
        operationId: 'settingSystemInfo',
        summary: '系统信息',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-系统信息']
    )]
    #[Permission(code: 'setting:systeminfo')]
    #[ResultResponse(new Result())]
    public function show(): Result
    {
        return $this->success($this->service->collect());
    }
}
