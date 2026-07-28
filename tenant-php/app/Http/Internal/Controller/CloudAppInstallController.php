<?php

declare(strict_types=1);

namespace App\Http\Internal\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Http\Internal\Middleware\InternalAuthMiddleware;
use App\Service\Cloud\CloudAppInstallService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/internal/cloud')]
#[Middleware(InternalAuthMiddleware::class)]
final class CloudAppInstallController extends AbstractController
{
    public function __construct(private readonly CloudAppInstallService $service) {}

    #[PostMapping(path: 'app-install')]
    public function install(RequestInterface $request): Result
    {
        $result = $this->service->install($request->all());
        if (($result['ok'] ?? false) !== true) {
            return $this->error((string) ($result['message'] ?? '安装失败'), $result);
        }

        return $this->success($result);
    }
}
