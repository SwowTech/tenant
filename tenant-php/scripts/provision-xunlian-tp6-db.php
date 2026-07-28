<?php

declare(strict_types=1);

/**
 * 为指定租户补导入 xunlian-trace-tp6 的 xlsy_* 表.
 * Usage: php scripts/provision-xunlian-tp6-db.php --tenant=31
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Model\OpsTenant;
use App\Service\App\XunlianTraceTp6DbProvisioner;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;
use App\Library\Tenant\DynamicTablePrefix;

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
    fwrite(STDERR, "Usage: php scripts/provision-xunlian-tp6-db.php --tenant=ID\n");
    exit(1);
}

/** @var DynamicTablePrefix $prefixSvc */
$prefixSvc = ApplicationContext::getContainer()->get(DynamicTablePrefix::class);
$tenant = $prefixSvc->withoutPrefix(fn () => OpsTenant::query()->find($tenantId));
if (! $tenant) {
    fwrite(STDERR, "tenant {$tenantId} not found\n");
    exit(1);
}
$prefix = (string) ($tenant->table_prefix ?: ('cy_' . $tenantId . '_'));
echo "provisioning tenant={$tenantId} prefix={$prefix}\n";

(new XunlianTraceTp6DbProvisioner())->provisionForTenant($tenantId, $prefix);

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        \Hyperf\Support\env('DB_HOST', '127.0.0.1'),
        (int) \Hyperf\Support\env('DB_PORT', 3306),
        \Hyperf\Support\env('DB_DATABASE', 'mineadmin')
    ),
    (string) \Hyperf\Support\env('DB_USERNAME', 'root'),
    (string) \Hyperf\Support\env('DB_PASSWORD', ''),
);
$n = count($pdo->query("SHOW TABLES LIKE " . $pdo->quote($prefix . 'xlsy_%'))->fetchAll());
echo "OK xlsy tables={$n}\n";
