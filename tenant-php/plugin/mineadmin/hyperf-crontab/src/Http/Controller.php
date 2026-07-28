<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\MineAdmin\Crontab\Http;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Result;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Controller as ControllerAnnotation;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;

#[ControllerAnnotation(prefix: '/admin/plugin/crontab')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
class Controller extends AbstractController
{
    public function __construct(
        private Service $service
    ) {}

    #[GetMapping(
        path: 'log/list',
    )]
    #[Permission('plugin:mine-admin:crontab:list')]
    public function logList(RequestInterface $request): Result
    {
        return $this->success(
            $this->service->logList(
                $request->all(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[GetMapping(
        path: 'list',
    )]
    #[Permission('plugin:mine-admin:crontab:list')]
    public function list(RequestInterface $request): Result
    {
        return $this->success(
            $this->service->page(
                $request->all(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[PostMapping(
        path: 'create',
    )]
    #[Permission('plugin:mine-admin:crontab:create')]
    public function create(FormRequest $request): Result
    {
        return $this->success(
            $this->service->create($request->validated())
        );
    }

    #[PutMapping(
        path: '{id}/save',
    )]
    #[Permission('plugin:mine-admin:crontab:save')]
    public function save(int $id, FormRequest $request): Result
    {
        if ($this->service->updateById($id, $request->validated())) {
            return $this->success(
                $this->service->findById($id)
            );
        }
        return $this->error('保存失败');
    }

    #[DeleteMapping(
        path: 'delete',
    )]
    #[Permission('plugin:mine-admin:crontab:delete')]
    public function delete(RequestInterface $request): Result
    {
        $ids = Arr::get($request->all(), 'ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的数据');
        }
        $deleteCount = $this->service->deleteById($ids);
        if ($deleteCount) {
            return $this->success(\sprintf('成功删除%s条数据', $deleteCount));
        }
        return $this->error('删除失败');
    }

    /**
     * @throws \Throwable
     */
    #[PostMapping(
        path: 'execute'
    )]
    #[Permission('plugin:mine-admin:crontab:execute')]
    public function execute(RequestInterface $request): Result
    {
        $ids = Arr::get($request->all(), 'ids', []);
        if (empty($ids)) {
            return $this->error('请选择一条任务执行');
        }
        $this->service->execute($ids);
        return $this->success();
    }
}
