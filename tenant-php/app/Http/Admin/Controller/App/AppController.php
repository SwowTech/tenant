<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\App;

use App\Exception\BusinessException;
use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Request\AppDataMigrateRequest;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\Common\ResultCode;
use App\Library\Tenant\TenantContext;
use App\Service\App\AppAdminPasswordService;
use App\Service\App\AppDataMigrateService;
use App\Service\App\AppInstallService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class AppController extends AbstractController
{
    public function __construct(
        private readonly AppDataMigrateService $migrateService,
        private readonly AppAdminPasswordService $adminPasswordService,
        private readonly AppInstallService $appInstallService,
    ) {}

    #[Post(
        path: '/admin/apps/migrate-data',
        operationId: 'appMigrateData',
        summary: '同库迁移应用数据（档位包）',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function migrateData(AppDataMigrateRequest $request): Result
    {
        $this->migrateService->migrate(
            (string) $request->input('from'),
            (string) $request->input('to'),
        );

        return $this->success();
    }

    #[Get(
        path: '/admin/apps/settings',
        operationId: 'appSettingsInfo',
        summary: '我的应用-设置信息（域名+管理员）',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function settingsInfo(RequestInterface $request): Result
    {
        $tenantId = $this->requireTenantId();
        $identifier = (string) $request->input('identifier', '');
        if ($identifier === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '缺少应用标识');
        }
        $passwordInfo = $this->adminPasswordService->info($identifier);
        $domains = $this->appInstallService->listDomains($tenantId, $identifier);

        return $this->success([
            'identifier' => $identifier,
            'domains' => $domains,
            'admin' => $passwordInfo,
        ]);
    }

    #[Post(
        path: '/admin/apps/domains',
        operationId: 'appSettingsBindDomain',
        summary: '我的应用-绑定域名',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function bindDomain(RequestInterface $request): Result
    {
        $tenantId = $this->requireTenantId();
        $identifier = (string) $request->input('identifier', '');
        $domain = (string) $request->input('domain', '');
        $scheme = (string) $request->input('scheme', 'https');
        $asPrimary = (bool) $request->input('is_primary', false);
        $this->adminPasswordService->info($identifier);
        $row = $this->appInstallService->addDomain($tenantId, $identifier, $domain, $scheme, $asPrimary);

        return $this->success($row);
    }

    #[Put(
        path: '/admin/apps/domains/{id:\d+}',
        operationId: 'appSettingsUpdateDomain',
        summary: '我的应用-更新域名',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function updateDomain(int $id, RequestInterface $request): Result
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

    #[Delete(
        path: '/admin/apps/domains/{id:\d+}',
        operationId: 'appSettingsDeleteDomain',
        summary: '我的应用-解绑域名',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function deleteDomain(int $id): Result
    {
        $tenantId = $this->requireTenantId();
        $this->appInstallService->removeDomain($id, $tenantId);

        return $this->success();
    }

    #[Post(
        path: '/admin/apps/change-admin-password',
        operationId: 'appChangeAdminPassword',
        summary: '我的应用-修改应用管理员密码',
        security: [['Bearer' => []]],
        tags: ['应用']
    )]
    #[ResultResponse(new Result())]
    public function changeAdminPassword(RequestInterface $request): Result
    {
        $this->requireTenantId();
        $identifier = (string) $request->input('identifier', '');
        $username = (string) $request->input('username', 'admin');
        $password = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', $password);
        if ($password !== $confirm) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '两次输入的密码不一致');
        }
        $this->adminPasswordService->change($identifier, $password, $username !== '' ? $username : null);

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
