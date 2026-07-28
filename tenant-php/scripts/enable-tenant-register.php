<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Setting\UserLoginSettingService;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
$container = require BASE_PATH . '/config/container.php';

$s = $container->get(UserLoginSettingService::class);
$cur = $s->save(['register_enabled' => true]);
echo 'register_enabled=' . (($cur['register_enabled'] ?? false) ? '1' : '0') . PHP_EOL;
