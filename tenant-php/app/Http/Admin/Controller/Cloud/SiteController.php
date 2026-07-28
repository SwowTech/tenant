<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Cloud;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\FounderMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\App\LocalAppCreateService;
use App\Service\Cloud\CloudInstalledCatalogService;
use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\HyperfServer;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
#[Controller(prefix: '/admin/cloud')]
final class SiteController extends AbstractController
{
    public function __construct(
        private readonly CloudSiteSettingService $service,
        private readonly CloudInstalledCatalogService $installedCatalog,
        private readonly LocalAppCreateService $localAppCreate,
    ) {}

    #[GetMapping(path: 'auth-url')]
    public function authUrl(): Result
    {
        return $this->success($this->service->buildAuthUrl());
    }

    #[GetMapping(path: 'site-info')]
    public function siteInfo(): Result
    {
        return $this->success($this->service->siteInfoForAdmin());
    }

    #[GetMapping(path: 'store-token')]
    public function storeToken(): Result
    {
        return $this->success($this->service->issueStoreTokenForAdmin());
    }

    #[GetMapping(path: 'installed-catalog')]
    public function installedCatalog(): Result
    {
        return $this->success($this->installedCatalog->list());
    }

    /** 查重：本地目录 + SaaS 云市场标识 */
    #[GetMapping(path: 'local-apps/check-identifier')]
    #[Middleware(middleware: FounderMiddleware::class, priority: 99)]
    public function checkLocalAppIdentifier(RequestInterface $request): Result
    {
        return $this->success(
            $this->localAppCreate->checkIdentifier((string) $request->input('identifier', ''))
        );
    }

    /** 创建本地应用（写入 apps/{vendor}/{app}） */
    #[PostMapping(path: 'local-apps')]
    #[Middleware(middleware: FounderMiddleware::class, priority: 99)]
    public function createLocalApp(RequestInterface $request): Result
    {
        return $this->success($this->localAppCreate->create($request->all()));
    }
}
