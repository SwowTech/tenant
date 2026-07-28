<?php

use think\facade\Env;

// 宿主 AppProcessManager 注入的 DB_* 优先于 .env
$dbHost = getenv('DB_HOST') ?: Env::get('database.hostname', '127.0.0.1');
$dbPort = getenv('DB_PORT') ?: Env::get('database.hostport', '3306');
$dbUser = getenv('DB_USERNAME') ?: Env::get('database.username', 'root');
$dbPass = getenv('DB_PASSWORD');
if ($dbPass === false) {
    $dbPass = Env::get('database.password', '123456');
}
// 与 host 共用 mineadmin 库，按 X-Tenant-Id / X-Tenant-Prefix 动态切换表前缀
$tenantId = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
$tenantPrefix = (string) ($_SERVER['HTTP_X_TENANT_PREFIX'] ?? '');
if ($tenantId > 0) {
    $dbPrefix = 'cy_' . $tenantId . '_xlsy_';
} elseif ($tenantPrefix !== '') {
    // 宿主注入 cy_31_ → cy_31_xlsy_
    $dbPrefix = rtrim($tenantPrefix, '_') . '_xlsy_';
} else {
    $dbPrefix = Env::get('database.prefix', 'xlsy_');
}

return [
    'default'         => Env::get('database.driver', 'mysql'),
    'time_query_rule' => [],
    'auto_timestamp'  => true,
    'datetime_format' => 'Y-m-d H:i:s',
    'connections'     => [
        'mysql' => [
            'type' => Env::get('database.type', 'mysql'),
            'hostname' => $dbHost,
            'database' => Env::get('database.database', 'mineadmin'),
            'username' => $dbUser,
            'password' => $dbPass,
            'hostport' => $dbPort,
            'params' => [],
            'charset' => Env::get('database.charset', 'utf8'),
            'prefix' => $dbPrefix,
            'debug' => Env::get('database.debug', true),
            'deploy' => 0,
            'rw_separate' => false,
            'master_num' => 1,
            'slave_no' => '',
            'fields_strict' => true,
            'break_reconnect' => false,
            'trigger_sql' => true,
            'fields_cache' => false,
            'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
        ],
    ],
];
