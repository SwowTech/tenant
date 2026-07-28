<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Wechat;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Admin\Request\WechatAccountRequest;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Repository\WechatAccountRepository;
use App\Service\WechatAccessTokenService;
use App\Service\WechatAccountService;
use Hyperf\HttpServer\Annotation\Middleware;
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
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly WechatAccountService $accountService,
        private readonly WechatAccessTokenService $tokenService,
        private readonly WechatAccountRepository $repository,
    ) {}

    #[Get(
        path: '/admin/wechat/account',
        operationId: 'wechatAccountShow',
        summary: '获取公众号接入配置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['微信公众号']
    )]
    #[Permission(code: 'wechat:account:view')]
    #[ResultResponse(new Result())]
    public function show(): Result
    {
        return $this->success($this->accountService->get() ?? $this->accountService->emptyView());
    }

    #[Put(
        path: '/admin/wechat/account',
        operationId: 'wechatAccountSave',
        summary: '保存公众号接入配置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['微信公众号']
    )]
    #[Permission(code: 'wechat:account:save')]
    #[ResultResponse(new Result())]
    public function update(WechatAccountRequest $request): Result
    {
        return $this->success($this->accountService->save($request->validated()));
    }

    #[Post(
        path: '/admin/wechat/account/refresh-token',
        operationId: 'wechatAccountRefreshToken',
        summary: '刷新 AccessToken',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['微信公众号']
    )]
    #[Permission(code: 'wechat:account:save')]
    #[ResultResponse(new Result())]
    public function refreshToken(): Result
    {
        $row = $this->repository->first();
        if ($row === null) {
            return $this->error('请先保存公众号配置');
        }
        $result = $this->tokenService->getToken($row, true);

        return $this->success([
            'ok' => true,
            'expires_in' => $result['expires_in'],
        ]);
    }
}
