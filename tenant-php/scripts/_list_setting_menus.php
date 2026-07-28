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
    "SELECT id, parent_id, name, path, component, status, meta FROM menu
     WHERE name = 'setting' OR name LIKE 'setting:%'
     ORDER BY parent_id, sort, id"
);
foreach ($rows as $r) {
    $m = json_decode((string) $r->meta, true) ?: [];
    echo implode("\t", [
        (string) $r->id,
        (string) $r->parent_id,
        $r->name,
        (string) $r->path,
        (string) $r->component,
        (string) $r->status,
        (string) ($m['title'] ?? ''),
    ]) . PHP_EOL;
}
