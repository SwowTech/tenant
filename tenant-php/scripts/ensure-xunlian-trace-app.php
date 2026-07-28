<?php

declare(strict_types=1);

/**
 * Enable swowtech/xunlian-trace for a tenant.
 * Usage: php scripts/ensure-xunlian-trace-app.php --tenant=1
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Service\App\AppInstallService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

$bootstrap = BASE_PATH . '/tests/bootstrap.php';
if (is_file($bootstrap)) {
    require $bootstrap;
} else {
    ClassLoader::init(handler: new ProcScanHandler());
    require BASE_PATH . '/config/container.php';
}

$tenantId = 0;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--tenant=')) {
        $tenantId = (int) substr($arg, 9);
    }
}
if ($tenantId <= 0) {
    fwrite(STDERR, "Usage: php scripts/ensure-xunlian-trace-app.php --tenant=ID\n");
    exit(1);
}

$appDir = BASE_PATH . '/apps/swowtech/xunlian-trace';
if (! is_file($appDir . '/app.json')) {
    fwrite(STDERR, "FAIL: app not found at apps/swowtech/xunlian-trace\n");
    exit(1);
}

/** @var AppInstallService $install */
$install = ApplicationContext::getContainer()->get(AppInstallService::class);
$install->enableForTenant($tenantId, 'swowtech/xunlian-trace', '1.0.0');
echo "OK enabled swowtech/xunlian-trace for tenant {$tenantId}\n";
