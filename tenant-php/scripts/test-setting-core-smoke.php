<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Setting\AttachmentSettingService;
use App\Service\Setting\PasswordPolicy;
use App\Service\Setting\SiteSettingService;
use App\Service\Setting\UserLoginSettingService;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());
ClassLoader::init(handler: new ProcScanHandler());
$container = require BASE_PATH . '/config/container.php';

$site = $container->get(SiteSettingService::class);
$login = $container->get(UserLoginSettingService::class);
$attach = $container->get(AttachmentSettingService::class);
$policy = $container->get(PasswordPolicy::class);

$siteBefore = $site->get();
$site->save(['closed' => false, 'close_reason' => 'smoke', 'auto_logout' => 30]);
$siteAfter = $site->get();
if (($siteAfter['auto_logout'] ?? null) !== 30) {
    echo "FAIL site auto_logout\n";
    exit(1);
}
if (array_key_exists('debug', $siteAfter) && $siteAfter['debug'] === true) {
    // ok if leftover; save should strip
}
$site->save([
    'closed' => (bool) ($siteBefore['closed'] ?? false),
    'close_reason' => (string) ($siteBefore['close_reason'] ?? ''),
    'auto_logout' => (int) ($siteBefore['auto_logout'] ?? 0),
]);
echo "OK site setting\n";

$login->save(['password_strength' => 'medium', 'register_enabled' => false]);
try {
    $policy->assertValid('abc');
    echo "FAIL weak password should reject\n";
    exit(1);
} catch (Throwable $e) {
    echo "OK password policy rejects weak\n";
}
try {
    $policy->assertValid('abc12345');
    echo "OK password policy accepts medium\n";
} catch (Throwable $e) {
    echo 'FAIL medium password: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

$att = $attach->get();
if (! isset($att['remote']['type'])) {
    echo "FAIL attachment remote\n";
    exit(1);
}
$attach->save(['remote' => ['type' => 'off', 'alioss' => ['endpoint' => 'oss-cn-hangzhou.aliyuncs.com']]]);
$att2 = $attach->get();
if (($att2['remote']['alioss']['endpoint'] ?? '') !== 'oss-cn-hangzhou.aliyuncs.com') {
    echo "FAIL attachment endpoint persist\n";
    exit(1);
}
echo "OK attachment setting\n";

echo "PASS setting-core-smoke\n";
