<?php

declare(strict_types=1);

namespace App\Http\AppGateway;

use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantInfo;
use App\Library\Tenant\TenantResolver;
use App\Service\App\AppProxyService;
use App\Service\App\AppStaticService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AppGatewayController
{
    /** @var list<string> */
    private const RESERVED_VENDORS = ['admin', 'swagger', 'internal', 'favicon.ico'];

    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly AppStaticService $staticService,
        private readonly AppProxyService $proxyService,
        private readonly RequestInterface $request,
    ) {}

    public function dispatch(string $vendor, string $app, string $path = ''): ResponseInterface
    {
        if (in_array(strtolower($vendor), self::RESERVED_VENDORS, true)) {
            return $this->plain(404, 'not found');
        }

        $identifier = AppPath::assertSafeIdentifier($vendor . '/' . $app);
        if (! is_file(AppPath::appDir($identifier) . '/app.json')) {
            return $this->plain(404, 'app not installed globally');
        }

        $tenant = $this->tenantResolver->resolve($this->request);
        if ($tenant === null) {
            return $this->plain(404, 'unknown tenant');
        }

        if (! $this->isEnabledForTenant($tenant, $identifier)) {
            return $this->plain(403, 'app not enabled for tenant');
        }

        TenantContext::set($tenant);
        $this->dynamicTablePrefix->apply($tenant->tablePrefix);
        try {
            $manifest = AppManifest::load($identifier);
            $apiPrefix = AppManifest::apiPrefix($manifest);
            $path = ltrim(str_replace('\\', '/', $path), '/');
            if ($path === $apiPrefix || str_starts_with($path, $apiPrefix . '/')) {
                $rest = $path === $apiPrefix ? '' : substr($path, strlen($apiPrefix) + 1);
                $upstreamPath = $apiPrefix . ($rest === '' ? '' : '/' . $rest);

                return $this->proxyService->forward(
                    $this->request,
                    $identifier,
                    $upstreamPath,
                    $tenant,
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
            return Db::table('tenant_installed_app')
                ->where('identifier', $identifier)
                ->where('status', 1)
                ->exists();
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    private function plain(int $status, string $text): ResponseInterface
    {
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus($status);
        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response = $response->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($text));

        return $response;
    }
}
