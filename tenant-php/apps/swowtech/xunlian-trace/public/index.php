<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Db.php';
require dirname(__DIR__) . '/app/Tenant.php';
require dirname(__DIR__) . '/app/Schema.php';
require dirname(__DIR__) . '/app/Api.php';

use TraceApp\Api;
use TraceApp\Schema;
use TraceApp\Tenant;

function gateway_secret(): string
{
    return getenv('APP_GATEWAY_SECRET') ?: 'dev-app-gateway-secret';
}

function require_gateway(): void
{
    $secret = $_SERVER['HTTP_X_APP_GATEWAY_SECRET'] ?? '';
    if (! hash_equals(gateway_secret(), (string) $secret)) {
        Api::fail('invalid gateway secret', 401);
        exit;
    }
}

function boot_tenant(): void
{
    $id = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
    if ($id <= 0) {
        Api::fail('missing tenant', 400);
        exit;
    }
    $headerPrefix = (string) ($_SERVER['HTTP_X_TENANT_PREFIX'] ?? '');
    try {
        Tenant::boot($id, $headerPrefix !== '' ? $headerPrefix : null);
    } catch (Throwable $e) {
        Api::fail($e->getMessage(), 400);
        exit;
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$sitePrefix = '/swowtech/xunlian-trace';
if (str_starts_with($uri, $sitePrefix)) {
    $uri = substr($uri, strlen($sitePrefix)) ?: '/';
}

if ($uri === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

if (! str_starts_with($uri, '/api/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'not found';
    return;
}

require_gateway();
boot_tenant();
$method = Api::method();

try {
    Schema::ensure();
} catch (Throwable $e) {
    Api::fail('schema: ' . $e->getMessage(), 500);
    return;
}

try {
    if ($uri === '/api/dashboard/summary' && $method === 'GET') {
        Api::json(Api::dashboard());
        return;
    }
    if ($uri === '/api/products' && $method === 'GET') {
        Api::json(Api::listProducts());
        return;
    }
    if ($uri === '/api/products' && $method === 'POST') {
        Api::json(Api::saveProduct(Api::body()));
        return;
    }
    if (preg_match('#^/api/products/(\d+)$#', $uri, $m) && $method === 'PUT') {
        Api::json(Api::saveProduct(Api::body(), (int) $m[1]));
        return;
    }
    if ($uri === '/api/batches' && $method === 'GET') {
        Api::json(Api::listBatches());
        return;
    }
    if ($uri === '/api/batches' && $method === 'POST') {
        Api::json(Api::createBatch(Api::body()));
        return;
    }
    if (preg_match('#^/api/batches/(\d+)/codes$#', $uri, $m) && $method === 'GET') {
        Api::json(Api::listBatchCodes((int) $m[1]));
        return;
    }
    if (preg_match('#^/api/codes/([^/]+)$#', $uri, $m) && $method === 'GET') {
        $ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        Api::json(Api::lookupCode(urldecode($m[1]), $ip));
        return;
    }
    if (preg_match('#^/api/codes/([^/]+)/writeoff$#', $uri, $m) && $method === 'POST') {
        Api::json(Api::writeoff(urldecode($m[1]), Api::body()));
        return;
    }
    Api::fail('not found', 404);
} catch (InvalidArgumentException $e) {
    Api::fail($e->getMessage(), 422);
} catch (Throwable $e) {
    Api::fail($e->getMessage(), 500);
}
