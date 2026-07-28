<?php

declare(strict_types=1);

/**
 * Smoke: welcome SaasBindService reads cloud.site (Weiqing-aligned).
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Cloud\CloudSiteSettingService;
use App\Service\Welcome\SaasBindService;
use App\Service\Welcome\SystemCheckService;
use Hyperf\Context\ApplicationContext;

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo "SKIP: Hyperf bootstrap unavailable (" . $e->getMessage() . ")\n";
    exit(0);
}

$container = ApplicationContext::getContainer();
/** @var SaasBindService $bindSvc */
$bindSvc = $container->get(SaasBindService::class);
/** @var CloudSiteSettingService $cloudSite */
$cloudSite = $container->get(CloudSiteSettingService::class);

echo "=== SaasBindService::status() ===\n";

// Force unbound snapshot then restore is heavy; assert shape of current status.
$status = $bindSvc->status();
echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

if (! array_key_exists('tenant', $status) || $status['tenant'] !== null) {
    echo "FAIL: tenant must be null\n";
    exit(1);
}
if (! str_contains((string) ($status['bind_url'] ?? ''), '/setting/cloud/register')) {
    echo "FAIL: bind_url must point to register site\n";
    exit(1);
}

if ($cloudSite->isBound()) {
    if (! ($status['bound'] ?? false) || empty($status['site']['key'])) {
        echo "FAIL: expected bound site shape\n";
        exit(1);
    }
    if (($status['message'] ?? '') !== '已注册云站点') {
        echo "FAIL: unexpected bound message\n";
        exit(1);
    }
    echo "PASS: bound status shape OK\n\n";
} else {
    if (($status['bound'] ?? true) !== false || ($status['site'] ?? 'x') !== null) {
        echo "FAIL: expected unbound shape\n";
        exit(1);
    }
    echo "PASS: unbound status shape OK\n\n";
}

// Round-trip: save smoke bind then expect bound, then leave data (smoke style)
$marker = 'welcome-bind-' . time();
$cloudSite->saveFromPush([
    'key' => $marker,
    'token' => $marker . '-token',
    'url' => 'http://127.0.0.1:9501',
    'username' => 'welcome-smoke',
    'phone' => '138****0000',
    'email' => 'w***@example.com',
]);
$bound = $bindSvc->status();
if (! $bound['bound'] || ($bound['site']['key'] ?? '') !== $marker || $bound['tenant'] !== null) {
    echo "FAIL: after saveFromPush status not bound\n";
    echo json_encode($bound, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}
echo "PASS: bound after cloud.site push\n\n";

$checkResult = (new SystemCheckService($bindSvc))->run();
echo "=== SystemCheckService::run() ===\n";
echo "check_num={$checkResult['check_num']} check_wrong_num={$checkResult['check_wrong_num']}\n";
if (! str_contains($checkResult['report_text'], '云站点绑定')) {
    echo "FAIL: report missing 云站点绑定\n";
    exit(1);
}
$saasItem = null;
foreach ($checkResult['items'] as $item) {
    if (($item['key'] ?? '') === 'saas_bind') {
        $saasItem = $item;
        break;
    }
}
if ($saasItem === null || ! ($saasItem['ok'] ?? false)) {
    echo "FAIL: saas_bind must always pass (optional bind)\n";
    exit(1);
}
echo "PASS: system check label OK (bind optional, ok=true)\n";

exit(0);
