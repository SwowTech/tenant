<?php

declare(strict_types=1);

/**
 * Enable mineadmin/demo for a tenant.
 * Usage: php scripts/ensure-demo-app.php --tenant=1
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/tests/bootstrap.php';

use App\Service\App\AppInstallService;
use Hyperf\Context\ApplicationContext;

$tenantId = 0;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--tenant=')) {
        $tenantId = (int) substr($arg, 9);
    }
}
if ($tenantId <= 0) {
    fwrite(STDERR, "Usage: php scripts/ensure-demo-app.php --tenant=ID\n");
    exit(1);
}

/** @var AppInstallService $install */
$install = ApplicationContext::getContainer()->get(AppInstallService::class);
$install->enableForTenant($tenantId, 'mineadmin/demo', '1.0.0');
echo "OK enabled mineadmin/demo for tenant {$tenantId}\n";
