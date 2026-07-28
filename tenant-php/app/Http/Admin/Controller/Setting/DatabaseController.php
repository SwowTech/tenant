<?php

declare(strict_types=1);

namespace App\Http\Admin\Controller\Setting;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Service\Setting\DatabaseToolService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DatabaseController extends AbstractController
{
    public function __construct(
        private readonly DatabaseToolService $service,
    ) {}

    #[Get(
        path: '/admin/setting/tools/database/tables',
        operationId: 'settingDatabaseTables',
        summary: '数据表列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function tables(): Result
    {
        return $this->success(['list' => $this->service->listTables()]);
    }

    #[Post(
        path: '/admin/setting/tools/database/optimize',
        operationId: 'settingDatabaseOptimize',
        summary: '优化数据表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function optimize(RequestInterface $request): Result
    {
        $tables = $request->input('tables', []);
        if (! is_array($tables)) {
            $tables = [];
        }

        return $this->success($this->service->optimize(array_values(array_map('strval', $tables))));
    }

    #[Post(
        path: '/admin/setting/tools/database/backup',
        operationId: 'settingDatabaseBackup',
        summary: '分卷备份（单步）',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function backup(RequestInterface $request): Result
    {
        return $this->success($this->service->backupStep($request->all()));
    }

    #[Get(
        path: '/admin/setting/tools/database/backups',
        operationId: 'settingDatabaseBackups',
        summary: '备份列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function backups(): Result
    {
        return $this->success(['list' => $this->service->listBackups()]);
    }

    #[Delete(
        path: '/admin/setting/tools/database/backups/{dirname}',
        operationId: 'settingDatabaseBackupDelete',
        summary: '删除备份',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function deleteBackup(string $dirname): Result
    {
        $this->service->deleteBackup($dirname);

        return $this->success(true);
    }

    #[Post(
        path: '/admin/setting/tools/database/restore',
        operationId: 'settingDatabaseRestore',
        summary: '还原备份（单步）',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['设置-数据库工具']
    )]
    #[Permission(code: 'setting:tools:database')]
    #[ResultResponse(new Result())]
    public function restore(RequestInterface $request): Result
    {
        $dirname = (string) $request->input('dirname', '');
        $volumeIndex = (int) $request->input('volume_index', 0);

        return $this->success($this->service->restoreStep($dirname, $volumeIndex));
    }
}
