<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Setting;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Setting\SiteSettingService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SiteController extends AbstractController
{
    public function __construct(private readonly SiteSettingService $service) {}

    #[Get(
        path: '/admin/setting/site',
        operationId: 'settingSiteShow',
        summary: '获取站点设置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function show(): Result
    {
        return $this->success($this->service->get());
    }

    #[Put(
        path: '/admin/setting/site',
        operationId: 'settingSiteSave',
        summary: '保存站点设置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function update(): Result
    {
        return $this->success($this->service->save($this->getRequestData()));
    }

    #[Get(
        path: '/admin/setting/site/icp',
        operationId: 'settingSiteIcpList',
        summary: '备案执照列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function listIcp(RequestInterface $request): Result
    {
        return $this->success($this->service->listIcp((string) $request->input('keyword', '')));
    }

    #[Post(
        path: '/admin/setting/site/icp',
        operationId: 'settingSiteIcpCreate',
        summary: '添加备案执照',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function createIcp(): Result
    {
        return $this->success($this->service->createIcp($this->getRequestData()));
    }

    #[Put(
        path: '/admin/setting/site/icp/{id}',
        operationId: 'settingSiteIcpUpdate',
        summary: '更新备案执照',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function updateIcp(int $id): Result
    {
        return $this->success($this->service->updateIcp($id, $this->getRequestData()));
    }

    #[Delete(
        path: '/admin/setting/site/icp/{id}',
        operationId: 'settingSiteIcpDelete',
        summary: '删除备案执照',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-站点']
    )]
    #[Permission(code: 'setting:site')]
    #[ResultResponse(new Result())]
    public function deleteIcp(int $id): Result
    {
        $this->service->deleteIcp($id);

        return $this->success();
    }
}
