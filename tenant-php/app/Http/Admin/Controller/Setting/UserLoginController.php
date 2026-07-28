<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Setting;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Setting\UserLoginSettingService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class UserLoginController extends AbstractController
{
    public function __construct(private readonly UserLoginSettingService $service) {}

    #[Get(
        path: '/admin/setting/user-login',
        operationId: 'settingUserLoginShow',
        summary: '获取用户登录注册设置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-用户登录']
    )]
    #[Permission(code: 'setting:user-login')]
    #[ResultResponse(new Result())]
    public function show(): Result
    {
        return $this->success($this->service->get());
    }

    #[Put(
        path: '/admin/setting/user-login',
        operationId: 'settingUserLoginSave',
        summary: '保存用户登录注册设置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-用户登录']
    )]
    #[Permission(code: 'setting:user-login')]
    #[ResultResponse(new Result())]
    public function update(): Result
    {
        return $this->success($this->service->save($this->getRequestData()));
    }
}
