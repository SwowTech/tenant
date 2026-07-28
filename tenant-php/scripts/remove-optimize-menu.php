<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
require BASE_PATH . '/config/container.php';

$ids = Db::table('menu')
    ->where('name', 'setting:tools:optimize')
    ->orWhere('path', '/setting/tools/optimize')
    ->pluck('id')
    ->all();

foreach ($ids as $id) {
    Db::table('menu')->where('parent_id', $id)->delete();
    try {
        Db::table('role_belongs_menu')->where('menu_id', $id)->delete();
    } catch (Throwable) {
    }
    Db::table('menu')->where('id', $id)->delete();
}

echo 'OK removed setting:tools:optimize count=' . count($ids) . PHP_EOL;
