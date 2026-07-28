<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Setting\DatabaseToolService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
require BASE_PATH . '/config/container.php';

$svc = ApplicationContext::getContainer()->get(DatabaseToolService::class);
$tables = $svc->listTables();
echo 'OK tables=' . count($tables) . "\n";
if ($tables === []) {
    fwrite(STDERR, "FAIL empty tables\n");
    exit(1);
}

$need = array_values(array_map(
    static fn ($t) => $t['name'],
    array_filter($tables, static fn ($t) => $t['need_optimize'])
));
$opt = $svc->optimize(array_slice($need, 0, 3));
echo 'OK optimize done=' . count($opt['optimized']) . ' skipped=' . count($opt['skipped']) . "\n";

$step = $svc->backupStep(['status' => 1, 'start' => 1]);
$guard = 0;
while (! empty($step['continue']) && $guard < 500) {
    $step = $svc->backupStep([
        'last_table' => $step['last_table'] ?? '',
        'index' => $step['index'] ?? 0,
        'series' => $step['series'] ?? 1,
        'folder_suffix' => $step['folder_suffix'] ?? '',
        'volume_suffix' => $step['volume_suffix'] ?? '',
        'status' => 1,
    ]);
    ++$guard;
}
if (! empty($step['continue'])) {
    fwrite(STDERR, "FAIL backup not finished\n");
    exit(1);
}
$folder = (string) ($step['folder_suffix'] ?? '');
echo 'OK backup folder=' . $folder . "\n";
$list = $svc->listBackups();
echo 'OK backups=' . count($list) . "\n";
if ($folder !== '') {
    $svc->deleteBackup($folder);
    echo "OK deleted smoke backup\n";
}
echo "PASS database tool smoke\n";
