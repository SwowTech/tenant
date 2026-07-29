<?php

use think\facade\Env;

// 正式部署：宿主未注入 DB_* 时，从 user-php/.env 兜底
$hostEnv = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'host_env.php';
if (is_file($hostEnv)) {
    require_once $hostEnv;
    mine_apps_load_host_env();
}

// 宿主 AppProcessManager 注入的 DB_* 优先于应用本地 .env
$dbHost = getenv('DB_HOST');
$dbHost = ($dbHost !== false && $dbHost !== '') ? (string) $dbHost : (string) Env::get('database.hostname', '127.0.0.1');
$dbPort = getenv('DB_PORT');
$dbPort = ($dbPort !== false && $dbPort !== '') ? (string) $dbPort : (string) Env::get('database.hostport', '3306');
$dbUser = getenv('DB_USERNAME');
$dbUser = ($dbUser !== false && $dbUser !== '') ? (string) $dbUser : (string) Env::get('database.username', 'root');
$dbName = getenv('DB_DATABASE');
$dbName = ($dbName !== false && $dbName !== '') ? (string) $dbName : (string) Env::get('database.database', 'mineadmin');
$dbCharset = getenv('DB_CHARSET');
$dbCharset = ($dbCharset !== false && $dbCharset !== '') ? (string) $dbCharset : (string) Env::get('database.charset', 'utf8mb4');

$dbPass = getenv('DB_PASSWORD');
if ($dbPass === false) {
    // 未注入时才读应用 .env；勿写死 123456（正式环境必炸）
    $dbPass = (string) Env::get('database.password', '');
} else {
    $dbPass = (string) $dbPass;
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
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'hostport' => $dbPort,
            'params' => [],
            'charset' => $dbCharset,
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
