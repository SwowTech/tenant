<?php

declare(strict_types=1);

/**
 * Optional smoke: GET /mineadmin/demo/ via running user-php (requires tenant + install).
 * Usage: BASE=http://127.0.0.1:9501 TENANT=3 php scripts/test-app-gateway-smoke.php
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

$base = rtrim(getenv('BASE') ?: 'http://127.0.0.1:9501', '/');
$tenant = (int) (getenv('TENANT') ?: 0);
if ($tenant <= 0) {
    echo "SKIP gateway smoke (set TENANT=id)\n";
    exit(0);
}

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-Tenant-Id: {$tenant}\r\n",
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);
$url = $base . '/mineadmin/demo/';
$body = @file_get_contents($url, false, $ctx);
if (! is_string($body) || ! str_contains($body, 'Demo App')) {
    fwrite(STDERR, "FAIL static gateway {$url}\n");
    exit(1);
}
echo "OK gateway static\n";
