<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Cloud;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Cloud\CloudUpgradeService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Swagger\Annotation\HyperfServer;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
#[Controller(prefix: '/admin/cloud')]
final class UpgradeController extends AbstractController
{
    public function __construct(private readonly CloudUpgradeService $svc) {}

    #[GetMapping(path: 'upgrade')]
    public function index(): Result
    {
        return $this->success($this->svc->overview());
    }

    #[PostMapping(path: 'upgrade/check')]
    public function check(): Result
    {
        try {
            return $this->success($this->svc->check());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PostMapping(path: 'upgrade/start')]
    public function start(): Result
    {
        try {
            return $this->success($this->svc->start($this->getRequest()->input('agreed')));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    #[GetMapping(path: 'upgrade/task/{id}')]
    public function task(string $id): Result
    {
        try {
            return $this->success($this->svc->task($id));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }
}
