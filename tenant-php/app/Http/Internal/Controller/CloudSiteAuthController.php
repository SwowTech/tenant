<?php

declare(strict_types=1);

namespace App\Http\Internal\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Http\Internal\Middleware\InternalAuthMiddleware;
use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/internal/cloud')]
#[Middleware(InternalAuthMiddleware::class)]
final class CloudSiteAuthController extends AbstractController
{
    public function __construct(private readonly CloudSiteSettingService $service) {}

    #[PostMapping(path: 'site-auth')]
    public function siteAuth(RequestInterface $request): Result
    {
        try {
            $this->service->saveFromPush($request->all());
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(['ok' => true]);
    }
}
