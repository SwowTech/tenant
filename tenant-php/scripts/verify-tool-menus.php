<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use Hyperf\DbConnection\Db;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
require BASE_PATH . '/config/container.php';

$rows = Db::select(
    "SELECT id, parent_id, name, path, component FROM menu
     WHERE name LIKE 'setting:tools%'
        OR name LIKE 'setting:job%'
        OR name = 'setting:system-check'
        OR name = 'plugin:mine-admin:crontab'
     ORDER BY parent_id, sort"
);

foreach ($rows as $r) {
    echo implode("\t", [(string) $r->id, (string) $r->parent_id, $r->name, $r->path, (string) $r->component]) . PHP_EOL;
}

$gone = Db::table('menu')->whereIn('name', [
    'setting:tools:filecheck',
    'setting:tools:scan',
    'setting:tools:bom',
    'setting:tools:optimize',
    'setting:job:display',
])->count();
echo 'remaining_unwanted=' . $gone . PHP_EOL;
