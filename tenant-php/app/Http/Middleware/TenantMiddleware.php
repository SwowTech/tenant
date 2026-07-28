<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        // 创始人控制台操作平台名册，不能套租户表前缀
        if (str_starts_with($path, '/internal/')
            || str_starts_with($path, '/wechat/')
            || str_starts_with($path, '/admin/founder')) {
            // 清掉其它请求残留的动态前缀，避免查成 cy_x_ops_tenant
            $this->dynamicTablePrefix->reset();

            return $handler->handle($request);
        }

        $tenant = $this->resolver->resolve($request);
        if ($tenant === null) {
            return $handler->handle($request);
        }

        if ($tenant->status === 3) {
            throw new BusinessException(ResultCode::FORBIDDEN, '租户已冻结');
        }

        TenantContext::set($tenant);
        $this->dynamicTablePrefix->apply($tenant->tablePrefix);

        try {
            return $handler->handle($request);
        } finally {
            $this->dynamicTablePrefix->reset();
            TenantContext::clear();
        }
    }
}
