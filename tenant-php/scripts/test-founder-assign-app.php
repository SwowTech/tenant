<?php

declare(strict_types=1);

/**
 * Smoke: FounderAppAssignService assign into tenant_installed_app under tenant prefix.
 * Usage: php scripts/test-founder-assign-app.php
 *
 * Negatives always asserted. Positive path uses already-installed mine-admin/app-store
 * (install.lock present) → enable/write tenant row without Plugin::install.
 * If that plugin is missing, SKIP install but still assert prefix switch + write path.
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Exception\BusinessException;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
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

function faaMsg(Throwable $e): string
{
    if ($e instanceof BusinessException) {
        return (string) ($e->getResponse()->message ?? $e->getMessage());
    }

    return $e->getMessage();
}

/** @var FounderAppAssignService $assign */
$assign = ApplicationContext::getContainer()->get(FounderAppAssignService::class);
/** @var FounderTenantService $tenants */
$tenants = ApplicationContext::getContainer()->get(FounderTenantService::class);
/** @var DynamicTablePrefix $prefix */
$prefix = ApplicationContext::getContainer()->get(DynamicTablePrefix::class);
/** @var ConfigInterface $config */
$config = ApplicationContext::getContainer()->get(ConfigInterface::class);

// --- negatives ---
try {
    $assign->assign(1, '../evil', '1.0.0');
    echo "FAIL: illegal identifier should throw\n";
    exit(1);
} catch (BusinessException $e) {
    $msg = faaMsg($e);
    if (! str_contains($msg, 'identifier') && ! str_contains($msg, '非法')) {
        echo "FAIL: illegal identifier message unexpected: {$msg}\n";
        exit(1);
    }
    echo "PASS: illegal identifier rejected\n";
} catch (Throwable $e) {
    echo 'FAIL: illegal identifier threw unexpected: ' . faaMsg($e) . "\n";
    exit(1);
}

try {
    $assign->assign(999999991, 'mine-admin/app-store', '1.0.0');
    echo "FAIL: missing tenant should throw\n";
    exit(1);
} catch (BusinessException $e) {
    $msg = faaMsg($e);
    if (! str_contains($msg, '租户不存在')) {
        echo "FAIL: missing tenant message unexpected: {$msg}\n";
        exit(1);
    }
    echo "PASS: missing tenant rejected\n";
} catch (Throwable $e) {
    echo 'FAIL: missing tenant threw unexpected: ' . faaMsg($e) . "\n";
    exit(1);
}

// --- prepare tenant ---
$code = 'faa-' . substr((string) time(), -8);
$domain = $code . '.test';
try {
    $tenant = $tenants->create([
        'code' => $code,
        'name' => 'Founder Assign App Smoke',
        'domain' => $domain,
        'admin_user' => 'admin',
        'admin_pass' => '123456',
        'remark' => 'test-founder-assign-app',
    ]);
} catch (Throwable $e) {
    echo 'FAIL: create tenant: ' . faaMsg($e) . "\n";
    exit(1);
}

$tenantId = (int) $tenant['id'];
$expectedPrefix = TenantInfo::tablePrefixForId($tenantId);
$physicalTable = $expectedPrefix . 'tenant_installed_app';

$pluginId = 'mine-admin/app-store';
$pluginPath = Plugin::PLUGIN_PATH . '/' . $pluginId;
$hasLock = is_file($pluginPath . '/' . Plugin::INSTALL_LOCK_FILE);
$skipInstall = ! $hasLock;

try {
    if ($skipInstall) {
        // No install.lock: assert prefix switch + write path without Plugin::install
        $prefix->apply($expectedPrefix);
        try {
            $cfgPrefix = (string) $config->get('databases.default.prefix');
            if ($cfgPrefix !== $expectedPrefix) {
                echo "FAIL: prefix not applied, got {$cfgPrefix}\n";
                exit(1);
            }
            $now = date('Y-m-d H:i:s');
            Db::table('tenant_installed_app')->updateOrInsert(
                ['identifier' => 'vendor/fixture-skip'],
                [
                    'version' => '0.0.1',
                    'status' => 1,
                    'config' => null,
                    'installed_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $row = Db::connection('default')->table($physicalTable)
                ->where('identifier', 'vendor/fixture-skip')->first();
            if ($row === null) {
                echo "FAIL: write path did not hit {$physicalTable}\n";
                exit(1);
            }
            echo "SKIP: install (no install.lock for {$pluginId}); prefix+write path OK\n";
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
        echo "PASS: prefix reset after assign\n";

        $row = Db::connection('default')->table($physicalTable)
            ->where('identifier', $pluginId)->first();
        if ($row === null) {
            echo "FAIL: no row in {$physicalTable} for {$pluginId}\n";
            exit(1);
        }
        if ((int) $row->status !== 1) {
            echo "FAIL: expected status=1, got {$row->status}\n";
            exit(1);
        }
        echo "PASS: assign wrote {$physicalTable} status=1\n";

        // re-assign enables again
        Db::connection('default')->table($physicalTable)
            ->where('identifier', $pluginId)->update(['status' => 2]);
        $assign->assign($tenantId, $pluginId, '1.0.0');
        $row2 = Db::connection('default')->table($physicalTable)
            ->where('identifier', $pluginId)->first();
        if ($row2 === null || (int) $row2->status !== 1) {
            echo "FAIL: re-assign did not re-enable status=1\n";
            exit(1);
        }
        echo "PASS: re-assign enables status=1\n";
    }

    if ($skipInstall) {
        $prefix->apply($expectedPrefix);
        try {
            $now = date('Y-m-d H:i:s');
            Db::table('tenant_installed_app')->updateOrInsert(
                ['identifier' => $pluginId],
                [
                    'version' => '1.0.0',
                    'status' => 1,
                    'config' => null,
                    'installed_at' => $now,
                    'updated_at' => $now,
                ]
            );
        } finally {
            $prefix->reset();
        }
    }

    // --- list / assignMany / setAppStatus ---
    $list = $assign->listTenantApps($tenantId);
    if (! is_array($list) || count($list) < 1) {
        echo "FAIL: listTenantApps expected >=1 row\n";
        exit(1);
    }
    echo 'PASS: listTenantApps count=' . count($list) . "\n";

    $many = $assign->assignMany($tenantId, [
        ['identifier' => 'mine-admin/app-store', 'version' => '1.0.0'],
    ]);
    if (($many['attached'] ?? 0) < 1) {
        echo "FAIL: assignMany attached\n";
        exit(1);
    }
    echo "PASS: assignMany attached={$many['attached']}\n";

    $assign->setAppStatus($tenantId, 'mine-admin/app-store', 2);
    $prefix->apply($tenant['table_prefix'] ?: TenantInfo::tablePrefixForId($tenantId));
    try {
        $row = Db::table('tenant_installed_app')->where('identifier', 'mine-admin/app-store')->first();
        if (! $row || (int) $row->status !== 2) {
            echo "FAIL: setAppStatus disable expected status=2\n";
            exit(1);
        }
    } finally {
        $prefix->reset();
    }
    echo "PASS: setAppStatus disabled\n";

    $assign->setAppStatus($tenantId, 'mine-admin/app-store', 1);
    $prefix->apply($tenant['table_prefix'] ?: TenantInfo::tablePrefixForId($tenantId));
    try {
        $row = Db::table('tenant_installed_app')->where('identifier', 'mine-admin/app-store')->first();
        if (! $row || (int) $row->status !== 1) {
            echo "FAIL: setAppStatus enable expected status=1\n";
            exit(1);
        }
    } finally {
        $prefix->reset();
    }
    echo "PASS: setAppStatus enabled\n";

    $tenants->update($tenantId, ['status' => FounderTenantService::STATUS_DISABLED]);
    try {
        $assign->listTenantApps($tenantId);
        echo "FAIL: inactive tenant list should throw\n";
        exit(1);
    } catch (BusinessException $e) {
        echo "PASS: inactive tenant list rejected\n";
    }
    $tenants->update($tenantId, ['status' => FounderTenantService::STATUS_ACTIVE]);

    $list = $assign->listAssignableApps();
    if (! is_array($list) || $list === []) {
        echo "FAIL: listAssignableApps empty\n";
        exit(1);
    }
    echo 'PASS: listAssignableApps count=' . count($list) . "\n";

    $ctrl = (string) file_get_contents(BASE_PATH . '/app/Http/Admin/Controller/Founder/AppController.php');
    if (! str_contains($ctrl, 'FounderMiddleware') || ! str_contains($ctrl, "path: 'apps'")) {
        echo "FAIL: AppController missing FounderMiddleware or apps route\n";
        exit(1);
    }
    if (! str_contains($ctrl, 'tenants/{id:') || ! str_contains($ctrl, '/apps')) {
        echo "FAIL: AppController missing assign route\n";
        exit(1);
    }
    echo "PASS: AppController has FounderMiddleware + apps routes\n";

    echo "PASS\n";
} catch (Throwable $e) {
    echo 'FAIL: ' . faaMsg($e) . "\n";
    exit(1);
} finally {
    try {
        $prefix->reset();
    } catch (Throwable) {
    }
    // keep tenant for inspection; optional cleanup of ops row only
    // OpsTenant::query()->where('id', $tenantId)->delete();
}

exit(0);
