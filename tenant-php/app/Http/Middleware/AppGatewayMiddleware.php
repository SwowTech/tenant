<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\AppGateway\AppGatewayDispatch;
use App\Library\App\AppDomainBinding;
use App\Library\App\AppPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 应用网关：
 * 1) 独立域名 → 直达绑定应用（无 /vendor/app 前缀；含 /admin）
 * 2) /{vendor}/{app}/… 路径反代
 */
final class AppGatewayMiddleware implements MiddlewareInterface
{
    /** 仅在非应用独立域名时跳过，交给平台 Hyperf 路由 */
    /** @var list<string> */
    private const SKIP_PREFIXES = ['/admin', '/api', '/internal', '/swagger', '/wechat'];

    public function __construct(
        private readonly AppGatewayDispatch $dispatch,
        private readonly AppDomainBinding $appDomainBinding,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $host = $request->getHeaderLine('Host');
        $host = $host !== '' ? explode(':', $host)[0] : '';
        $binding = $host !== '' ? $this->appDomainBinding->findByDomain($host) : null;

        // 绑定域名：整站交给应用（/admin、/api 等也是应用自己的路径）
        if ($binding !== null) {
            $identifier = AppPath::assertSafeIdentifier($binding['identifier']);
            [$vendor, $app] = explode('/', $identifier, 2);
            $rest = ltrim($path, '/');

            return $this->dispatch->handle(
                $request,
                $vendor,
                $app,
                $rest,
                $binding['tenant_id'],
                true,
            );
        }

        foreach (self::SKIP_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $handler->handle($request);
            }
        }

        if (! preg_match('#^/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)(?:/(.*))?$#', $path, $m)) {
            return $handler->handle($request);
        }

        $vendor = $m[1];
        $app = $m[2];
        $rest = (string) ($m[3] ?? '');
        if (in_array(strtolower($vendor), ['admin', 'favicon.ico'], true)) {
            return $handler->handle($request);
        }

        return $this->dispatch->handle($request, $vendor, $app, $rest);
    }
}
