<?php

declare(strict_types=1);

/**
 * Remove unused Weiqing-style tool placeholders from menu.
 * Keep setting:job group (parent for crontab plugin).
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

$names = [
    'setting:tools:filecheck',
    'setting:tools:scan',
    'setting:tools:bom',
    'setting:tools:optimize',
    'setting:job:display',
];

$paths = [
    '/setting/tools/filecheck',
    '/setting/tools/scan',
    '/setting/tools/bom',
    '/setting/tools/optimize',
];

$ids = Db::table('menu')
    ->where(function ($q) use ($names, $paths) {
        $q->whereIn('name', $names)->orWhereIn('path', $paths);
    })
    ->pluck('id')
    ->all();

$removed = 0;
foreach ($ids as $id) {
    try {
        Db::table('role_belongs_menu')->where('menu_id', $id)->delete();
    } catch (Throwable) {
    }
    $removed += Db::table('menu')->where('id', $id)->delete();
}

// Ensure setting:job group exists for crontab parent
$jobId = (int) (Db::table('menu')->where('name', 'setting:job')->value('id') ?? 0);
if ($jobId <= 0) {
    $rootId = (int) (Db::table('menu')->where('name', 'setting')->value('id') ?? 0);
    if ($rootId > 0) {
        $now = date('Y-m-d H:i:s');
        $jobId = (int) Db::table('menu')->insertGetId([
            'parent_id' => $rootId,
            'name' => 'setting:job',
            'path' => '',
            'component' => '',
            'redirect' => '',
            'status' => 1,
            'sort' => 40,
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'meta' => json_encode([
                'title' => '后台任务',
                'icon' => 'ri:task-line',
                'type' => 'M',
                'hidden' => 0,
                'breadcrumbEnable' => 1,
                'copyright' => 1,
                'cache' => 1,
                'affix' => 0,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo "created setting:job id={$jobId}\n";
    }
}

if ($jobId > 0) {
    $moved = Db::table('menu')
        ->where('name', 'plugin:mine-admin:crontab')
        ->where('parent_id', '<>', $jobId)
        ->update(['parent_id' => $jobId, 'sort' => 20, 'updated_at' => date('Y-m-d H:i:s')]);
    if ($moved) {
        echo "moved crontab under setting:job\n";
    }
}

echo 'OK removed menu rows=' . $removed . ' matched_ids=' . count($ids) . PHP_EOL;
