<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Library\App\AppProcessManager;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
require BASE_PATH . '/config/container.php';

$m = ApplicationContext::getContainer()->get(AppProcessManager::class);
$m->stop('swowtech/zzzcms');
echo "stopped\n";
$row = $m->ensureRunning('swowtech/zzzcms');
echo 'started listen=' . ($row['listen'] ?? '') . "\n";
