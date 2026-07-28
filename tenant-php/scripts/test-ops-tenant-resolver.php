<?php

declare(strict_types=1);

/**
 * Smoke: ops_tenant table + TenantResolver reads default connection.
 * Usage: php scripts/test-ops-tenant-resolver.php
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Tenant\TenantInfo;
use App\Library\Tenant\TenantResolver;
use App\Model\OpsTenant;
use Hyperf\Context\ApplicationContext;

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

$source = (string) file_get_contents(BASE_PATH . '/app/Library/Tenant/TenantResolver.php');
if (str_contains($source, "connection('platform')->table('tenant')")) {
    echo "FAIL: TenantResolver still references platform.tenant\n";
    exit(1);
}
echo "PASS: TenantResolver has no platform.tenant reads\n";

$marker = 'ops-resolver-' . time();
$tenant = OpsTenant::query()->create([
    'code' => $marker,
    'name' => 'Ops Tenant Smoke',
    'domain' => $marker,
    'table_prefix' => '',
    'status' => 5,
]);

/** @var TenantResolver $resolver */
$resolver = ApplicationContext::getContainer()->get(TenantResolver::class);

$byId = $resolver->fromId((int) $tenant->id);
if ($byId === null || $byId->code !== $marker) {
    echo "FAIL: fromId returned unexpected result\n";
    exit(1);
}
if ($byId->tablePrefix !== TenantInfo::tablePrefixForId((int) $tenant->id)) {
    echo "FAIL: fromId table_prefix fallback expected cy_{id}_\n";
    exit(1);
}
echo "PASS: fromId OK, prefix={$byId->tablePrefix}\n";

$byDomain = $resolver->fromDomain($marker);
if ($byDomain === null || $byDomain->id !== (int) $tenant->id) {
    echo "FAIL: fromDomain returned unexpected result\n";
    exit(1);
}
echo "PASS: fromDomain OK\n";

$byCode = $resolver->fromCode($marker);
if ($byCode === null || $byCode->id !== (int) $tenant->id) {
    echo "FAIL: fromCode returned unexpected result\n";
    exit(1);
}
echo "PASS: fromCode OK\n";

OpsTenant::query()->where('id', $tenant->id)->delete();
echo "PASS: ops_tenant resolver smoke complete\n";

exit(0);
