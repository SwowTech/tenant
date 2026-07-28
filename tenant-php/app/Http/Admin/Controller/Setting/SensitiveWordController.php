<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Setting;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Setting\SensitiveWordSettingService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SensitiveWordController extends AbstractController
{
    public function __construct(private readonly SensitiveWordSettingService $service) {}

    #[Get(
        path: '/admin/setting/sensitive-word',
        operationId: 'settingSensitiveWordShow',
        summary: '获取敏感词列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-敏感词']
    )]
    #[Permission(code: 'setting:sensitive-word')]
    #[ResultResponse(new Result())]
    public function show(): Result
    {
        return $this->success($this->service->get());
    }

    #[Post(
        path: '/admin/setting/sensitive-word',
        operationId: 'settingSensitiveWordAdd',
        summary: '添加敏感词',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-敏感词']
    )]
    #[Permission(code: 'setting:sensitive-word')]
    #[ResultResponse(new Result())]
    public function add(): Result
    {
        $data = $this->getRequestData();

        return $this->success($this->service->add((string) ($data['word'] ?? '')));
    }

    #[Delete(
        path: '/admin/setting/sensitive-word',
        operationId: 'settingSensitiveWordDelete',
        summary: '删除敏感词',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-敏感词']
    )]
    #[Permission(code: 'setting:sensitive-word')]
    #[ResultResponse(new Result())]
    public function delete(RequestInterface $request): Result
    {
        $data = $this->getRequestData();
        $word = (string) ($request->input('word') ?? $data['word'] ?? '');

        return $this->success($this->service->delete($word));
    }
}
