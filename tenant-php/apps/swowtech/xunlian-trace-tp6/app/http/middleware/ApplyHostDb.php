<?php

declare(strict_types=1);

namespace app\http\middleware;

use think\facade\Config;
use think\facade\Db;

/**
 * 每个请求强制套用宿主注入的 DB_* 与租户表前缀（避免 config 缓存/进程旧 env）.
 */
class ApplyHostDb
{
    public function handle($request, \Closure $next)
    {
        // app/http/middleware → 上 5 级到 apps/
        $hostEnv = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'host_env.php';
        if (is_file($hostEnv)) {
            require_once $hostEnv;
            if (function_exists('mine_apps_load_host_env')) {
                mine_apps_load_host_env();
            }
        }

        $mysql = config('database.connections.mysql');
        if (! is_array($mysql)) {
            $mysql = [];
        }

        $host = getenv('DB_HOST');
        if (is_string($host) && $host !== '') {
            $mysql['hostname'] = $host;
        }
        $port = getenv('DB_PORT');
        if (is_string($port) && $port !== '') {
            $mysql['hostport'] = $port;
        }
        $name = getenv('DB_DATABASE');
        if (is_string($name) && $name !== '') {
            $mysql['database'] = $name;
        }
        $user = getenv('DB_USERNAME');
        if (is_string($user) && $user !== '') {
            $mysql['username'] = $user;
        }
        if (getenv('DB_PASSWORD') !== false) {
            $mysql['password'] = (string) getenv('DB_PASSWORD');
        }
        $charset = getenv('DB_CHARSET');
        if (is_string($charset) && $charset !== '') {
            $mysql['charset'] = $charset;
        }

        $tenantId = (int) ($request->header('x-tenant-id') ?: 0);
        $tenantPrefix = (string) ($request->header('x-tenant-prefix') ?: '');
        if ($tenantId > 0) {
            $mysql['prefix'] = 'cy_' . $tenantId . '_xlsy_';
        } elseif ($tenantPrefix !== '') {
            $mysql['prefix'] = rtrim($tenantPrefix, '_') . '_xlsy_';
        }

        Config::set([
            'default' => 'mysql',
            'connections' => [
                'mysql' => $mysql,
            ],
        ], 'database');

        try {
            Db::connect('mysql', true);
        } catch (\Throwable) {
            // 交给业务/异常处理暴露真实错误
        }

        return $next($request);
    }
}
