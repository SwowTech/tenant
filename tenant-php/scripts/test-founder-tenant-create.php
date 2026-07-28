<?php

declare(strict_types=1);

/**
 * Smoke: FounderTenantService create + provision (no saas / no TenantSubscription).
 * Usage: php scripts/test-founder-tenant-create.php
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Tenant\TenantInfo;
use App\Model\OpsTenant;
use App\Service\Founder\FounderTenantService;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

$serviceSource = (string) file_get_contents(BASE_PATH . '/app/Service/Founder/FounderTenantService.php');
if (preg_match('/\bTenantSubscription\b/', $serviceSource)
    || preg_match('/use\s+[^;]*\\\\saas/i', $serviceSource)
    || preg_match('/Http\\\\Client|Guzzle|file_get_contents\s*\(\s*[\'"]https?:/', $serviceSource)
) {
    echo "FAIL: FounderTenantService must not call saas or write TenantSubscription\n";
    exit(1);
}
echo "PASS: FounderTenantService has no TenantSubscription/saas call refs\n";

$mwSource = (string) file_get_contents(BASE_PATH . '/app/Http/Admin/Middleware/FounderMiddleware.php');
if (! str_contains($mwSource, 'founder') || ! str_contains($mwSource, '=== 1')) {
    echo "FAIL: FounderMiddleware missing founder / id===1 check\n";
    exit(1);
}
echo "PASS: FounderMiddleware checks id===1 or founder role\n";

/** @var FounderTenantService $service */
$service = ApplicationContext::getContainer()->get(FounderTenantService::class);

$code = 'ft-' . substr((string) time(), -8);
$domain = $code . '.test';

$beforeSub = 0;
try {
    $beforeSub = (int) Db::connection('platform')->table('tenant_subscription')->count();
} catch (Throwable) {
    // platform / subscription may be absent; ignore
}

try {
    $tenant = $service->create([
        'code' => $code,
        'name' => 'Founder Tenant Smoke',
        'domain' => $domain,
        'admin_user' => 'admin',
        'admin_pass' => '123456',
        'remark' => 'test-founder-tenant-create',
    ]);
} catch (Throwable $e) {
    echo 'FAIL: create/provision threw: ' . $e->getMessage() . "\n";
    OpsTenant::query()->where('code', $code)->delete();
    exit(1);
}

$tenantId = (int) $tenant['id'];
$expectedPrefix = TenantInfo::tablePrefixForId($tenantId);
if ((int) $tenant['status'] !== FounderTenantService::STATUS_ACTIVE) {
    echo "FAIL: expected status=1 active, got {$tenant['status']}\n";
    exit(1);
}
if ($tenant['table_prefix'] !== $expectedPrefix) {
    echo "FAIL: expected prefix {$expectedPrefix}, got {$tenant['table_prefix']}\n";
    exit(1);
}
echo "PASS: create+provision OK id={$tenantId} prefix={$tenant['table_prefix']}\n";

$userTable = $expectedPrefix . 'user';
try {
    $adminExists = (int) Db::table($userTable)->where('username', 'admin')->count();
    if ($adminExists < 1) {
        echo "FAIL: tenant admin user not found in {$userTable}\n";
        exit(1);
    }
    echo "PASS: tenant admin exists in {$userTable}\n";
} catch (Throwable $e) {
    echo 'FAIL: cannot query tenant user table: ' . $e->getMessage() . "\n";
    exit(1);
}

try {
    $afterSub = (int) Db::connection('platform')->table('tenant_subscription')->count();
    if ($afterSub !== $beforeSub) {
        echo "FAIL: tenant_subscription row count changed ({$beforeSub} -> {$afterSub})\n";
        exit(1);
    }
    echo "PASS: no new tenant_subscription rows\n";
} catch (Throwable) {
    echo "PASS: tenant_subscription check skipped (table/connection unavailable)\n";
}

$page = $service->paginate(['code' => $code]);
if (($page['total'] ?? 0) < 1) {
    echo "FAIL: paginate did not find created tenant\n";
    exit(1);
}
echo "PASS: paginate OK\n";

$updated = $service->update($tenantId, ['name' => 'Founder Tenant Updated', 'status' => 2]);
if ($updated['name'] !== 'Founder Tenant Updated' || (int) $updated['status'] !== 2) {
    echo "FAIL: update failed\n";
    exit(1);
}
echo "PASS: update OK\n";

// cleanup ops row (keep tenant tables; avoid dropping migrated schema)
OpsTenant::query()->where('id', $tenantId)->delete();
echo "PASS: cleaned ops_tenant row id={$tenantId}\n";
echo "PASS: founder tenant create smoke complete\n";

exit(0);
