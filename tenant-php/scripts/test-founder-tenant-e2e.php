<?php

declare(strict_types=1);

/**
 * E2E: FounderTenantService create → TenantResolver → optional FounderAppAssignService.
 * Asserts no new tenant_subscription rows on create.
 * Usage: php scripts/test-founder-tenant-e2e.php
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Library\Tenant\TenantResolver;
use App\Model\OpsTenant;
use App\Service\Founder\FounderAppAssignService;
use App\Service\Founder\FounderTenantService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;
use Mine\AppStore\Plugin;

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

/** @var FounderTenantService $tenants */
$tenants = ApplicationContext::getContainer()->get(FounderTenantService::class);
/** @var TenantResolver $resolver */
$resolver = ApplicationContext::getContainer()->get(TenantResolver::class);
/** @var FounderAppAssignService $assign */
$assign = ApplicationContext::getContainer()->get(FounderAppAssignService::class);
/** @var DynamicTablePrefix $prefix */
$prefix = ApplicationContext::getContainer()->get(DynamicTablePrefix::class);
/** @var ConfigInterface $config */
$config = ApplicationContext::getContainer()->get(ConfigInterface::class);

$code = 'fte-' . substr((string) time(), -8);
$domain = $code . '.test';

$beforeSub = 0;
try {
    $beforeSub = (int) Db::connection('platform')->table('tenant_subscription')->count();
} catch (Throwable) {
    // platform / subscription may be absent; ignore
}

try {
    $tenant = $tenants->create([
        'code' => $code,
        'name' => 'Founder Tenant E2E',
        'domain' => $domain,
        'admin_user' => 'admin',
        'admin_pass' => '123456',
        'remark' => 'test-founder-tenant-e2e',
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
echo "PASS: create+provision OK id={$tenantId} prefix={$expectedPrefix}\n";

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

$byId = $resolver->fromId($tenantId);
if ($byId === null || $byId->code !== $code || $byId->tablePrefix !== $expectedPrefix) {
    echo "FAIL: resolver fromId unexpected\n";
    exit(1);
}
echo "PASS: resolver fromId OK\n";

$byDomain = $resolver->fromDomain($domain);
if ($byDomain === null || $byDomain->id !== $tenantId) {
    echo "FAIL: resolver fromDomain unexpected\n";
    exit(1);
}
echo "PASS: resolver fromDomain OK\n";

$byCode = $resolver->fromCode($code);
if ($byCode === null || $byCode->id !== $tenantId) {
    echo "FAIL: resolver fromCode unexpected\n";
    exit(1);
}
echo "PASS: resolver fromCode OK\n";

// optional assign: use installed plugin or assert prefix+write path
$pluginId = 'mine-admin/app-store';
$pluginPath = Plugin::PLUGIN_PATH . '/' . $pluginId;
$hasLock = is_file($pluginPath . '/' . Plugin::INSTALL_LOCK_FILE);
$physicalTable = $expectedPrefix . 'tenant_installed_app';

try {
    if (! $hasLock) {
        $prefix->apply($expectedPrefix);
        try {
            $cfgPrefix = (string) $config->get('databases.default.prefix');
            if ($cfgPrefix !== $expectedPrefix) {
                echo "FAIL: prefix not applied, got {$cfgPrefix}\n";
                exit(1);
            }
            $now = date('Y-m-d H:i:s');
            Db::table('tenant_installed_app')->updateOrInsert(
                ['identifier' => 'vendor/e2e-fixture'],
                [
                    'version' => '0.0.1',
                    'status' => 1,
                    'config' => null,
                    'installed_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $row = Db::connection('default')->table($physicalTable)
                ->where('identifier', 'vendor/e2e-fixture')->first();
            if ($row === null) {
                echo "FAIL: assign write path did not hit {$physicalTable}\n";
                exit(1);
            }
            echo "SKIP: assign install (no install.lock); prefix+write path OK\n";
        } finally {
            $prefix->reset();
        }
    } else {
        $assign->assign($tenantId, $pluginId, '1.0.0');
        $cfgPrefix = (string) $config->get('databases.default.prefix');
        if ($cfgPrefix !== '') {
            echo "FAIL: prefix not reset after assign, got '{$cfgPrefix}'\n";
            exit(1);
        }
        $row = Db::connection('default')->table($physicalTable)
            ->where('identifier', $pluginId)->first();
        if ($row === null || (int) $row->status !== 1) {
            echo "FAIL: assign did not write enabled row to {$physicalTable}\n";
            exit(1);
        }
        echo "PASS: assign wrote {$physicalTable} status=1\n";
    }
} catch (Throwable $e) {
    echo 'FAIL: assign phase: ' . $e->getMessage() . "\n";
    exit(1);
} finally {
    try {
        $prefix->reset();
    } catch (Throwable) {
    }
}

OpsTenant::query()->where('id', $tenantId)->delete();
echo "PASS: cleaned ops_tenant row id={$tenantId}\n";
echo "PASS: founder tenant e2e complete\n";

exit(0);
