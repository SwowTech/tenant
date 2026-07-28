<?php

declare(strict_types=1);

namespace App\Http\Internal\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Service\Tenant\TenantProvisionService;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use App\Http\Internal\Middleware\InternalAuthMiddleware;

#[Controller(prefix: '/internal/tenant')]
#[Middleware(InternalAuthMiddleware::class)]
final class TenantProvisionController extends AbstractController
{
    public function __construct(private readonly TenantProvisionService $provisionService) {}

    #[PostMapping(path: 'provision')]
    public function provision(): Result
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $id = (int) $request->input('id');
        $adminUser = (string) $request->input('admin_user', 'admin');
        $adminPass = (string) $request->input('admin_pass', '123456');

        if ($id <= 0) {
            return $this->error('无效的租户 ID');
        }

        $this->provisionService->provision($id, $adminUser, $adminPass);

        return $this->success(['tenant_id' => $id]);
    }
}
