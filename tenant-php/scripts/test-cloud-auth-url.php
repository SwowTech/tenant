<?php

declare(strict_types=1);

/**
 * Smoke test for CloudSiteSettingService buildAuthUrl + siteInfoForAdmin.
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

$service->saveFromPush([
    'key' => 'auth-url-key-' . time(),
    'token' => 'auth-url-token-' . time(),
    'url' => 'http://127.0.0.1:9501',
    'username' => 'smoke-user',
    'phone' => '138****0000',
    'email' => 'auth-url-' . time() . '@example.com',
]);

$auth = $service->buildAuthUrl();
$info = $service->siteInfoForAdmin();

if (! str_contains($auth['url'], '__auth=')) {
    echo "FAIL: auth.url missing __auth=\n";
    exit(1);
}

$required = ['bound', 'key', 'token_masked', 'url', 'username', 'phone', 'email', 'bound_at', 'auth'];
foreach ($required as $field) {
    if (! array_key_exists($field, $info)) {
        echo "FAIL: siteInfo missing field {$field}\n";
        exit(1);
    }
}

if ($info['token_masked'] === '' || ! str_contains($info['token_masked'], '****')) {
    echo "FAIL: token_masked not masked\n";
    exit(1);
}

if (! is_bool($info['bound']) || ! $info['bound']) {
    echo "FAIL: expected bound=true\n";
    exit(1);
}

echo 'auth.url=' . substr($auth['url'], 0, 80) . "...\n";
echo 'token_masked=' . $info['token_masked'] . "\n";
echo "PASS\n";
exit(0);
