<?php

declare(strict_types=1);

namespace App\Http\Internal\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Library\Tenant\TenantInfo;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Http\Internal\Middleware\InternalAuthMiddleware;

#[Controller(prefix: '/internal/tenant')]
#[Middleware(InternalAuthMiddleware::class)]
final class TenantStatsController extends AbstractController
{
    #[GetMapping(path: '{id:\d+}/stats')]
    public function stats(int $id): Result
    {
        $prefix = TenantInfo::tablePrefixForId($id);
        $database = (string) config('databases.default.database');
        $count = (int) Db::table('information_schema.tables')
            ->where('table_schema', $database)
            ->where('table_name', 'like', $prefix . '%')
            ->count();

        return $this->success([
            'tenant_id' => $id,
            'table_prefix' => $prefix,
            'table_count' => $count,
        ]);
    }
}
