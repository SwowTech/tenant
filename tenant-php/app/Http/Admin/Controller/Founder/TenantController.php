<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Founder;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\FounderMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Result;
use App\Service\Founder\FounderTenantService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\HyperfServer;

#[HyperfServer(name: 'http')]
#[Controller(prefix: '/admin/founder/tenants')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: FounderMiddleware::class, priority: 99)]
final class TenantController extends AbstractController
{
    public function __construct(
        private readonly FounderTenantService $service,
    ) {}

    #[GetMapping(path: '')]
    public function list(RequestInterface $request): Result
    {
        return $this->success($this->service->paginate($request->all()));
    }

    #[GetMapping(path: 'domain-available')]
    public function domainAvailable(RequestInterface $request): Result
    {
        $domain = (string) $request->input('domain', '');
        $excludeRaw = $request->input('exclude_id');
        $excludeId = is_numeric($excludeRaw) ? (int) $excludeRaw : null;
        $normalized = strtolower(trim($domain));
        $available = $this->service->isDomainAvailable($domain, $excludeId);

        return $this->success([
            'domain' => $normalized,
            'available' => $available,
            'root_host' => \App\Library\Support\AppUrl::host(),
            'access_url' => $available
                ? \App\Library\Support\AppUrl::tenantAccessUrl($normalized)
                : '',
        ]);
    }

    #[GetMapping(path: 'suggest-domain')]
    public function suggestDomain(): Result
    {
        $domain = $this->service->suggestDomain();

        return $this->success([
            'domain' => $domain,
            'available' => true,
            'root_host' => \App\Library\Support\AppUrl::host(),
            'access_url' => \App\Library\Support\AppUrl::tenantAccessUrl($domain),
        ]);
    }

    #[PostMapping(path: '')]
    public function create(RequestInterface $request): Result
    {
        return $this->success($this->service->create($request->all()));
    }

    #[GetMapping(path: '{id:\d+}')]
    public function detail(int $id): Result
    {
        return $this->success($this->service->detail($id));
    }

    #[PutMapping(path: '{id:\d+}')]
    public function update(int $id, RequestInterface $request): Result
    {
        return $this->success($this->service->update($id, $request->all()));
    }

    #[PostMapping(path: '{id:\d+}/provision')]
    public function provision(int $id, RequestInterface $request): Result
    {
        $adminUser = $request->input('admin_user');
        $adminPass = $request->input('admin_pass');

        return $this->success($this->service->reprovision(
            $id,
            is_string($adminUser) ? $adminUser : null,
            is_string($adminPass) ? $adminPass : null,
        ));
    }

    #[PostMapping(path: '{id:\d+}/enter')]
    public function enter(int $id): Result
    {
        return $this->success($this->service->enterAsAdmin($id));
    }
}
