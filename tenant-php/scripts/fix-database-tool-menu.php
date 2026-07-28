<?php

declare(strict_types=1);

/**
 * Point setting:tools:database menu to real Vue page.
 * Usage: php scripts/fix-database-tool-menu.php
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
require BASE_PATH . '/config/container.php';

$component = 'base/views/setting/tools/database/index';
$n = Db::table('menu')->where('name', 'setting:tools:database')->update([
    'component' => $component,
    'updated_at' => date('Y-m-d H:i:s'),
]);

// meta.icon
$rows = Db::table('menu')->where('name', 'setting:tools:database')->get();
foreach ($rows as $row) {
    $meta = json_decode((string) $row->meta, true) ?: [];
    $meta['icon'] = 'ri:database-2-line';
    $meta['title'] = $meta['title'] ?? '数据库';
    Db::table('menu')->where('id', $row->id)->update([
        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ]);
}

echo "OK updated {$n} menu row(s) -> {$component}\n";
