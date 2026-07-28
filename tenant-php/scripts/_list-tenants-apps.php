<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/tests/bootstrap.php';

use Hyperf\DbConnection\Db;

$rows = Db::select('SELECT id, code, status, table_prefix FROM ops_tenant ORDER BY id');
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
$tables = Db::select("SHOW TABLES LIKE 'cy\\_%\\_tenant_installed_app'");
foreach ($tables as $t) {
    echo json_encode($t, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
