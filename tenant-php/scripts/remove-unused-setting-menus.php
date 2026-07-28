<?php

declare(strict_types=1);

/**
 * Remove unused setting menus; keep useful site/attachment/login items.
 */
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

$exact = [
    'setting:logs',
    'setting:oauth',
    'setting:oauth:page',
    'setting:ip-whitelist',
    'setting:ip-whitelist:page',
    'setting:sensitive-word',
    'setting:sensitive-word:page',
    'setting:tools:filecheck',
    'setting:tools:scan',
    'setting:tools:bom',
    'setting:tools:optimize',
    'setting:job:display',
];

$paths = [
    '/setting/logs',
    '/setting/oauth',
    '/setting/ip-whitelist',
    '/setting/sensitive-word',
];

$ids = Db::table('menu')
    ->where(function ($q) use ($exact, $paths) {
        $q->whereIn('name', $exact)->orWhereIn('path', $paths);
    })
    ->pluck('id')
    ->all();

// Also drop button children under removed pages
$childIds = [];
if ($ids) {
    $childIds = Db::table('menu')->whereIn('parent_id', $ids)->pluck('id')->all();
}
$all = array_values(array_unique(array_merge($ids, $childIds)));

$removed = 0;
foreach ($all as $id) {
    try {
        Db::table('role_belongs_menu')->where('menu_id', $id)->delete();
    } catch (Throwable) {
    }
    $removed += Db::table('menu')->where('id', $id)->delete();
}

echo 'OK removed=' . $removed . PHP_EOL;
