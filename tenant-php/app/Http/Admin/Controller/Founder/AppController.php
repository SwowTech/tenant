<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Founder;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\FounderMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Result;
use App\Library\Tenant\DynamicTablePrefix;
use App\Model\OpsTenant;
use App\Service\App\AppInstallService;
use App\Service\Founder\FounderAppAssignService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\HyperfServer;

/**
 * 创始人：租户应用域名绑定（多域名）.
 * 需登录访问令牌（Bearer）+ 创始人身份.
 */
#[HyperfServer(name: 'http')]
#[Controller(prefix: '/admin/founder')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: FounderMiddleware::class, priority: 99)]
final class AppController extends AbstractController
{
    public function __construct(
        private readonly FounderAppAssignService $service,
        private readonly AppInstallService $appInstallService,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    #[GetMapping(path: 'apps')]
    public function listApps(): Result
    {
        return $this->success($this->service->listAssignableApps());
    }

    #[GetMapping(path: 'tenants/{id:\d+}/apps')]
    public function listTenantApps(int $id): Result
    {
        return $this->success($this->service->listTenantApps($id));
    }

    #[PostMapping(path: 'tenants/{id:\d+}/apps')]
    public function assign(int $id, RequestInterface $request): Result
    {
        $years = (int) $request->input('years', 0);
        $months = (int) $request->input('months', 0);
        $apps = $request->input('apps');
        if (is_array($apps)) {
            return $this->success($this->service->assignMany($id, $apps, $years, $months));
        }

        $identifier = $request->input('identifier');
        $version = $request->input('version');
        $expiresAt = \App\Library\App\AppLicense::calcExpiresAt($years, $months);
        $this->service->assign(
            $id,
            is_string($identifier) ? $identifier : '',
            is_string($version) ? $version : '',
            $expiresAt,
        );

        return $this->success(['expires_at' => $expiresAt]);
    }

    #[PutMapping(path: 'tenants/{id:\d+}/apps/{identifier:.+}')]
    public function updateApp(int $id, string $identifier, RequestInterface $request): Result
    {
        $statusRaw = $request->input('status');
        $status = is_numeric($statusRaw) ? (int) $statusRaw : null;

        $hasYears = $request->has('years');
        $hasMonths = $request->has('months');
        $years = $hasYears || $hasMonths ? (int) $request->input('years', 0) : null;
        $months = $hasYears || $hasMonths ? (int) $request->input('months', 0) : null;

        // 兼容旧调用：只传 status
        if ($status !== null && $years === null && $months === null) {
            $this->service->setAppStatus($id, urldecode($identifier), $status);

            return $this->success();
        }

        return $this->success($this->service->updateTenantApp(
            $id,
            urldecode($identifier),
            $status,
            $years,
            $months,
        ));
    }

    /**
     * 全平台应用域名列表：GET /admin/founder/app-domains?tenant_id=&identifier=
     */
    #[GetMapping(path: 'app-domains')]
    public function listDomains(RequestInterface $request): Result
    {
        $tenantIdRaw = $request->input('tenant_id');
        $tenantId = is_numeric($tenantIdRaw) ? (int) $tenantIdRaw : null;
        $identifier = (string) $request->input('identifier', '');
        $list = $this->appInstallService->listDomains(
            $tenantId && $tenantId > 0 ? $tenantId : null,
            $identifier !== '' ? $identifier : null,
        );

        $tenantIds = array_values(array_unique(array_map(static fn (array $r) => (int) $r['tenant_id'], $list)));
        $tenantMap = [];
        if ($tenantIds !== []) {
            $tenantMap = $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantIds) {
                $map = [];
                $rows = OpsTenant::query()
                    ->whereIn('id', $tenantIds)
                    ->get(['id', 'name', 'domain', 'code']);
                foreach ($rows as $t) {
                    $map[(int) $t->id] = $t;
                }

                return $map;
            });
        }

        $enriched = array_map(static function (array $row) use ($tenantMap) {
            $tid = (int) $row['tenant_id'];
            $t = $tenantMap[$tid] ?? null;
            $row['tenant_name'] = $t ? (string) $t->name : '';
            $row['tenant_domain'] = $t ? (string) $t->domain : '';
            $row['tenant_code'] = $t ? (string) $t->code : '';

            return $row;
        }, $list);

        return $this->success(['list' => $enriched]);
    }

    /**
     * 绑定应用独立域名（新增一条）：POST /admin/founder/app-domains
     * body: { tenant_id, identifier, domain, scheme?, is_primary? }
     */
    #[PostMapping(path: 'app-domains')]
    public function createDomain(RequestInterface $request): Result
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $identifier = (string) $request->input('identifier', '');
        $domain = (string) $request->input('domain', '');
        $scheme = (string) $request->input('scheme', 'https');
        $asPrimary = (bool) $request->input('is_primary', false);
        $row = $this->appInstallService->addDomain($tenantId, $identifier, $domain, $scheme, $asPrimary);

        return $this->success($row);
    }

    #[PutMapping(path: 'app-domains/{id:\d+}')]
    public function updateDomain(int $id, RequestInterface $request): Result
    {
        $found = $this->appInstallService->findDomain($id);
        if ($found === null) {
            return $this->error('域名绑定不存在');
        }

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

        return $this->success($this->appInstallService->updateDomain($id, $found['tenant_id'], $data));
    }

    #[DeleteMapping(path: 'app-domains/{id:\d+}')]
    public function destroyDomain(int $id): Result
    {
        $found = $this->appInstallService->findDomain($id);
        if ($found === null) {
            return $this->error('域名绑定不存在');
        }
        $this->appInstallService->removeDomain($id, $found['tenant_id']);

        return $this->success();
    }

    /**
     * 兼容旧接口：POST /admin/founder/tenants/{id}/apps/{identifier}/domain
     */
    #[PostMapping(path: 'tenants/{id:\d+}/apps/{identifier:.+}/domain')]
    public function bindDomain(int $id, string $identifier, RequestInterface $request): Result
    {
        $domain = (string) $request->input('domain', '');
        $scheme = (string) $request->input('scheme', 'https');
        $asPrimary = (bool) $request->input('is_primary', true);
        $row = $this->appInstallService->addDomain($id, urldecode($identifier), $domain, $scheme, $asPrimary);

        return $this->success($row);
    }

    #[DeleteMapping(path: 'tenants/{id:\d+}/apps/{identifier:.+}/domain')]
    public function unbindDomain(int $id, string $identifier): Result
    {
        $this->appInstallService->unbindDomain($id, urldecode($identifier));

        return $this->success();
    }

    #[GetMapping(path: 'tenants/{id:\d+}/apps/{identifier:.+}/domain')]
    public function getDomain(int $id, string $identifier): Result
    {
        $decoded = urldecode($identifier);
        $list = $this->appInstallService->listDomains($id, $decoded);

        return $this->success([
            'public_base' => $this->appInstallService->getBoundDomain($id, $decoded),
            'list' => $list,
        ]);
    }
}
