<?php

declare(strict_types=1);

namespace App\Http\AppGateway;

use App\Library\App\AppDomainBinding;
use App\Library\App\AppLicense;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantInfo;
use App\Library\Tenant\TenantResolver;
use App\Service\App\AppProxyService;
use App\Service\App\AppStaticService;
use Hyperf\DbConnection\Db;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AppGatewayDispatch
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly AppStaticService $staticService,
        private readonly AppProxyService $proxyService,
        private readonly AppDomainBinding $appDomainBinding,
    ) {}

    public function handle(
        ServerRequestInterface $request,
        string $vendor,
        string $app,
        string $path,
        ?int $forcedTenantId = null,
        bool $viaAppDomain = false,
    ): ResponseInterface {
        if (! is_file(AppPath::appDir($vendor . '/' . $app) . '/app.json')) {
            return $this->plain(404, 'app not found');
        }

        $identifier = AppPath::assertSafeIdentifier($vendor . '/' . $app);
        $tenant = $forcedTenantId !== null && $forcedTenantId > 0
            ? $this->tenantResolver->fromId($forcedTenantId)
            : $this->tenantResolver->resolve($request);
        if ($tenant === null) {
            return $this->plain(404, 'unknown tenant');
        }

        if (! $this->isEnabledForTenant($tenant, $identifier)) {
            return $this->plain(403, 'app not enabled for tenant');
        }

        // TP6 溯源：安装记录在但库表未导入时自动补建（幂等）
        if ($identifier === \App\Service\App\XunlianTraceTp6DbProvisioner::IDENTIFIER) {
            try {
                (new \App\Service\App\XunlianTraceTp6DbProvisioner())->provisionForTenant(
                    $tenant->id,
                    $tenant->tablePrefix !== '' ? $tenant->tablePrefix : ('cy_' . $tenant->id . '_'),
                );
            } catch (\Throwable $e) {
                return $this->plain(500, 'app db provision failed: ' . $e->getMessage());
            }
        }

        $publicBase = $this->appDomainBinding->publicBase($tenant->id, $identifier);

        TenantContext::set($tenant);
        $this->dynamicTablePrefix->apply($tenant->tablePrefix);
        try {
            $manifest = AppManifest::load($identifier);
            $path = ltrim(str_replace('\\', '/', $path), '/');

            // 独立域名下 SPA 仍可能请求 /vendor/app/... 绝对资源路径，剥掉标识前缀
            if ($viaAppDomain) {
                $idPrefix = $identifier . '/';
                if ($path === $identifier) {
                    $path = '';
                } elseif (str_starts_with($path, $idPrefix)) {
                    $path = substr($path, strlen($idPrefix));
                }
            }

            if (AppManifest::proxyAll($manifest)) {
                // SPA/静态资源（如 /admin/*.css）优先由宿主直出，避免 PHP built-in + TP6 误判成路由 404
                if ($this->isPublicStaticPath($path) || AppManifest::isStaticAssetPath($path)) {
                    $webRoot = AppManifest::webDir($manifest, $identifier);
                    $candidate = $webRoot . '/' . $path;
                    if (is_file($candidate)) {
                        return $this->staticService->response($identifier, $path);
                    }

                    // 不再回落到 TP6（否则返回 application/json 的 Not Found，难排查）
                    return $this->plain(404, 'static not found: ' . $candidate);
                }

                return $this->proxyService->forward(
                    $request,
                    $identifier,
                    $path,
                    $tenant,
                    $publicBase,
                    $viaAppDomain,
                );
            }

            $apiPrefix = AppManifest::apiPrefix($manifest);
            if ($apiPrefix !== '' && ($path === $apiPrefix || str_starts_with($path, $apiPrefix . '/'))) {
                $rest = $path === $apiPrefix ? '' : substr($path, strlen($apiPrefix) + 1);
                $upstreamPath = $apiPrefix . ($rest === '' ? '' : '/' . $rest);

                return $this->proxyService->forward(
                    $request,
                    $identifier,
                    $upstreamPath,
                    $tenant,
                    $publicBase,
                    $viaAppDomain,
                );
            }

            return $this->staticService->response($identifier, $path);
        } finally {
            $this->dynamicTablePrefix->reset();
            TenantContext::clear();
        }
    }

    private function isEnabledForTenant(TenantInfo $tenant, string $identifier): bool
    {
        $this->dynamicTablePrefix->apply($tenant->tablePrefix);
        try {
            $row = Db::table('tenant_installed_app')
                ->where('identifier', $identifier)
                ->where('status', 1)
                ->first(['expires_at']);
            if ($row === null) {
                return false;
            }
            $expiresAt = isset($row->expires_at) && $row->expires_at !== null && $row->expires_at !== ''
                ? (string) $row->expires_at
                : null;

            return ! AppLicense::isExpired($expiresAt);
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    /** 带扩展名的路径按静态资源处理（admin SPA 的 js/css/map/图片等） */
    private function isPublicStaticPath(string $path): bool
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext !== '' && ! in_array($ext, ['php', 'phtml'], true);
    }

    private function plain(int $status, string $text): ResponseInterface
    {
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus($status);
        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        // 避免 Cloudflare 把 404 缓存 4 小时（max-age=14400）导致修完仍白屏
        if ($status >= 400) {
            $response = $response->withHeader('Cache-Control', 'no-store');
            $response = $response->withHeader('CDN-Cache-Control', 'no-store');
        }
        $response = $response->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($text));

        return $response;
    }
}
