<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\App;

use App\Exception\BusinessException;
use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\Common\ResultCode;
use App\Library\Tenant\TenantContext;
use App\Service\App\AppInstallService;
use App\Service\App\TenantInstalledAppQueryService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\HyperfServer;
use Mine\Access\Attribute\Permission;

/**
 * 租户自助：为本租户已安装应用绑定多个独立域名.
 * 需登录访问令牌（Bearer）.
 */
#[HyperfServer(name: 'http')]
#[Controller(prefix: '/admin/app-domains')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class AppDomainController extends AbstractController
{
    public function __construct(
        private readonly AppInstallService $appInstallService,
        private readonly TenantInstalledAppQueryService $installedApps,
    ) {}

    #[GetMapping(path: '')]
    #[Permission(code: 'setting:app-domains')]
    public function index(RequestInterface $request): Result
    {
        $tenantId = $this->requireTenantId();
        $identifier = (string) $request->input('identifier', '');

        return $this->success([
            'list' => $this->appInstallService->listDomains($tenantId, $identifier !== '' ? $identifier : null),
            'apps' => array_map(
                static fn (array $app) => [
                    'identifier' => $app['identifier'],
                    'title' => $app['title'] ?? $app['identifier'],
                ],
                $this->installedApps->listEnabled(),
            ),
        ]);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'setting:app-domains')]
    public function store(RequestInterface $request): Result
    {
        $tenantId = $this->requireTenantId();
        $identifier = (string) $request->input('identifier', '');
        $domain = (string) $request->input('domain', '');
        $scheme = (string) $request->input('scheme', 'https');
        $asPrimary = (bool) $request->input('is_primary', false);
        $row = $this->appInstallService->addDomain($tenantId, $identifier, $domain, $scheme, $asPrimary);

        return $this->success($row);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'setting:app-domains')]
    public function update(int $id, RequestInterface $request): Result
    {
        $tenantId = $this->requireTenantId();
        $data = [];
        if ($request->has('domain')) {
            $data['domain'] = (string) $request->input('domain');
        }
        if ($request->has('scheme')) {
            $data['scheme'] = (string) $request->input('scheme');
        }
        if ($request->has('is_primary')) {
            $data['is_primary'] = (bool) $request->input('is_primary');
        }

        return $this->success($this->appInstallService->updateDomain($id, $tenantId, $data));
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'setting:app-domains')]
    public function destroy(int $id): Result
    {
        $tenantId = $this->requireTenantId();
        $this->appInstallService->removeDomain($id, $tenantId);

        return $this->success();
    }

    private function requireTenantId(): int
    {
        $id = TenantContext::id();
        if ($id === null || $id <= 0) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请先选择租户（X-Tenant-Id）');
        }

        return $id;
    }
}
