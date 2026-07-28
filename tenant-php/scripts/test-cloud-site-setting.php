<?php

declare(strict_types=1);

/**
 * Smoke test for CloudSiteSettingService (saveFromPush + isBound).
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\Context\ApplicationContext;

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

/** @var CloudSiteSettingService $service */
$service = ApplicationContext::getContainer()->get(CloudSiteSettingService::class);

$testEmail = 'smoke-' . time() . '@example.com';

$service->saveFromPush([
    'key' => 'smoke-site-key-' . time(),
    'token' => 'smoke-site-token-' . time(),
    'url' => 'http://127.0.0.1:9501',
    'username' => 'smoke-user',
    'phone' => '138****0000',
    'email' => $testEmail,
]);

$site = $service->getSite();
$bound = $service->isBound() ? 1 : 0;

echo "bound={$bound}\n";
echo 'key=' . $site['key'] . "\n";
echo 'email=' . $site['email'] . "\n";

if ($bound !== 1 || $site['key'] === '') {
    echo "FAIL: expected bound=1 and non-empty key\n";
    exit(1);
}

if ($site['email'] !== $testEmail) {
    echo "FAIL: expected email={$testEmail}, got {$site['email']}\n";
    exit(1);
}

echo "PASS\n";
exit(0);
