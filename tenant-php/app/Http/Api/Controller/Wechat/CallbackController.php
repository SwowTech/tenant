<?php

declare(strict_types=1);

namespace App\Http\Api\Controller\Wechat;

use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantResolver;
use App\Repository\WechatAccountRepository;
use App\Service\WechatCallbackService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[HyperfServer(name: 'http')]
final class CallbackController
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly WechatAccountRepository $repository,
        private readonly WechatCallbackService $callbackService,
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
    ) {}

    #[Get(
        path: '/wechat/callback/{tenantCode}',
        operationId: 'wechatCallbackGet',
        summary: '微信服务器 URL 校验',
        tags: ['微信公众号']
    )]
    #[Post(
        path: '/wechat/callback/{tenantCode}',
        operationId: 'wechatCallbackPost',
        summary: '微信服务器消息推送',
        tags: ['微信公众号']
    )]
    public function handle(string $tenantCode): PsrResponseInterface
    {
        if ($tenantCode === 'default' || $tenantCode === '_') {
            // 单库本地开发：无平台租户时使用空表前缀
            $this->dynamicTablePrefix->reset();
            TenantContext::clear();
        } else {
            $tenant = $this->tenantResolver->fromCode($tenantCode);
            if ($tenant === null || $tenant->status === 3) {
                return $this->response->raw('invalid tenant')->withStatus(404);
            }
            TenantContext::set($tenant);
            $this->dynamicTablePrefix->apply($tenant->tablePrefix);
        }

        try {
            $account = $this->repository->first();
            if ($account === null || $account->token === '') {
                return $this->response->raw('not configured')->withStatus(400);
            }

            $signature = (string) $this->request->input('signature', '');
            $timestamp = (string) $this->request->input('timestamp', '');
            $nonce = (string) $this->request->input('nonce', '');
            if (! $this->callbackService->checkSignature($account->token, $signature, $timestamp, $nonce)) {
                return $this->response->raw('invalid signature')->withStatus(403);
            }

            if (strtoupper($this->request->getMethod()) === 'GET') {
                return $this->response->raw((string) $this->request->input('echostr', ''));
            }

            return $this->response->raw('');
        } finally {
            $this->dynamicTablePrefix->reset();
            TenantContext::clear();
        }
    }
}
