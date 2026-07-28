<?php

declare(strict_types=1);

/**
 * Smoke: TenantResolver custom_domain.
 * Usage: php scripts/test-tenant-resolver-custom-domain.php
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/tests/bootstrap.php';

use App\Library\Tenant\TenantResolver;
use App\Model\OpsTenant;
use Hyperf\Context\ApplicationContext;

/** @var TenantResolver $resolver */
$resolver = ApplicationContext::getContainer()->get(TenantResolver::class);

$host = 'demo-app-custom-' . bin2hex(random_bytes(4)) . '.local.test';
$row = OpsTenant::query()->where('status', 1)->orderBy('id')->first();
if ($row === null) {
    fwrite(STDERR, "SKIP: no active ops_tenant\n");
    exit(0);
}
$original = $row->custom_domain;
$row->custom_domain = $host;
$row->save();

try {
    $info = $resolver->fromCustomDomain($host);
    if ($info === null || $info->id !== (int) $row->id) {
        fwrite(STDERR, "FAIL fromCustomDomain\n");
        exit(1);
    }
    echo "OK fromCustomDomain id={$info->id}\n";
} finally {
    $row->custom_domain = $original;
    $row->save();
}

echo "PASS tenant custom_domain resolver\n";
