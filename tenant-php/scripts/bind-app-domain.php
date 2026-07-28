<?php

declare(strict_types=1);

/**
 * 绑定应用独立域名.
 * Usage: php scripts/bind-app-domain.php --tenant=33 --app=swowtech/xunlian-trace-tp6 --domain=sy.localhost --scheme=http
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
$app = '';
$domain = '';
$scheme = 'https';
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--tenant=')) {
        $tenantId = (int) substr($arg, 9);
    } elseif (str_starts_with($arg, '--app=')) {
        $app = substr($arg, 6);
    } elseif (str_starts_with($arg, '--domain=')) {
        $domain = substr($arg, 9);
    } elseif (str_starts_with($arg, '--scheme=')) {
        $scheme = substr($arg, 9);
    }
}
if ($tenantId <= 0 || $app === '' || $domain === '') {
    fwrite(STDERR, "Usage: php scripts/bind-app-domain.php --tenant=ID --app=vendor/app --domain=sy.example.com [--scheme=http]\n");
    exit(1);
}

/** @var AppInstallService $install */
$install = ApplicationContext::getContainer()->get(AppInstallService::class);
$base = $install->bindDomain($tenantId, $app, $domain, $scheme);
echo "OK bound {$app} for tenant {$tenantId} => {$base}\n";
echo "DNS/hosts: point {$domain} to this server, then open {$base}/qr?c=CODE\n";
