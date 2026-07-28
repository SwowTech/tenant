<?php

declare(strict_types=1);

/**
 * Unit/smoke: CloudAppInstallService negatives + hash chain.
 * Does NOT run Task 6 full e2e. Plugin install step is SKIP'd via _skip_plugin_install.
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Cloud\CloudAppInstallService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;
use Psr\Container\ContainerInterface;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

function caiFail(string $msg): never
{
    echo "FAIL: {$msg}\n";
    exit(1);
}

function caiOk(string $label): void
{
    echo "{$label}=ok\n";
}

/**
 * @return array{0: resource, 1: string} [proc, modeFile]
 */
function caiStartServer(string $phpBin, int $port, string $fixtureZip, string $token, string $verifyMode): array
{
    $modeFile = sys_get_temp_dir() . '/cai-verify-mode-' . getmypid() . '.txt';
    file_put_contents($modeFile, $verifyMode);

    $router = sys_get_temp_dir() . '/cloud-app-install-router-' . getmypid() . '.php';
    $fixtureExport = var_export($fixtureZip, true);
    $tokenExport = var_export($token, true);
    $modeFileExport = var_export($modeFile, true);

    $routerCode = <<<PHP
<?php
\$uri = parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
\$fixture = {$fixtureExport};
\$expected = {$tokenExport};
\$modeFile = {$modeFileExport};
if (\$uri === '/pkg.zip' && is_file(\$fixture)) {
    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize(\$fixture));
    readfile(\$fixture);
    return true;
}
if (\$uri === '/store/license/verify') {
    header('Content-Type: application/json');
    \$token = \$_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? '';
    if (! hash_equals(\$expected, \$token)) {
        echo json_encode(['code' => 401, 'message' => 'Invalid internal token', 'data' => []]);
        return true;
    }
    \$mode = is_file(\$modeFile) ? trim((string) file_get_contents(\$modeFile)) : 'deny';
    if (\$mode === 'allow') {
        echo json_encode(['code' => 200, 'message' => 'ok', 'data' => ['ok' => true, 'license_key' => 'k']]);
    } else {
        echo json_encode(['code' => 200, 'message' => 'ok', 'data' => ['ok' => false, 'message' => '未找到有效授权']]);
    }
    return true;
}
http_response_code(404);
echo 'not found';
return true;
PHP;
    file_put_contents($router, $routerCode);

    $logOut = sys_get_temp_dir() . '/cai-server-out.log';
    $logErr = sys_get_temp_dir() . '/cai-server-err.log';
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $logOut, 'w'],
        2 => ['file', $logErr, 'w'],
    ];
    $cmd = sprintf(
        '%s -S 127.0.0.1:%d %s',
        escapeshellarg($phpBin),
        $port,
        escapeshellarg($router)
    );
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (! is_resource($proc)) {
        caiFail('start fixture http server');
    }
    for ($i = 0; $i < 30; ++$i) {
        usleep(100000);
        $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
        if (is_resource($fp)) {
            fclose($fp);
            return [$proc, $modeFile, $router];
        }
    }
    $err = is_file($logErr) ? (string) file_get_contents($logErr) : '';
    proc_terminate($proc);
    proc_close($proc);
    caiFail('fixture http server not ready: ' . $err);
}

function caiFindFreePort(): int
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        caiFail('socket_create');
    }
    if (! socket_bind($socket, '127.0.0.1', 0)) {
        socket_close($socket);
        caiFail('socket_bind');
    }
    socket_getsockname($socket, $addr, $port);
    socket_close($socket);

    return $port;
}

$fixtureZip = __DIR__ . '/fixtures/cloud-app-min.zip';
if (! is_file($fixtureZip)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/build-cloud-app-min-zip.php'), $buildCode);
    if ($buildCode !== 0 || ! is_file($fixtureZip)) {
        caiFail('fixture zip missing');
    }
}
$goodHash = hash_file('sha256', $fixtureZip);
if (! is_string($goodHash) || strlen($goodHash) !== 64) {
    caiFail('fixture hash');
}

$port = caiFindFreePort();
$fixtureCopy = sys_get_temp_dir() . '/cloud-app-min-' . getmypid() . '.zip';
if (! copy($fixtureZip, $fixtureCopy)) {
    caiFail('copy fixture');
}

[$proc, $modeFile, $router] = caiStartServer(PHP_BINARY, $port, $fixtureCopy, 'test-internal-token', 'deny');

putenv('STORE_PACKAGE_URL_ALLOWLIST=127.0.0.1,localhost');
$_ENV['STORE_PACKAGE_URL_ALLOWLIST'] = '127.0.0.1,localhost';
$_SERVER['STORE_PACKAGE_URL_ALLOWLIST'] = '127.0.0.1,localhost';
putenv('SAAS_PHP_PUBLIC_URL=http://127.0.0.1:' . $port);
$_ENV['SAAS_PHP_PUBLIC_URL'] = 'http://127.0.0.1:' . $port;
$_SERVER['SAAS_PHP_PUBLIC_URL'] = 'http://127.0.0.1:' . $port;
putenv('INTERNAL_API_TOKEN=test-internal-token');
$_ENV['INTERNAL_API_TOKEN'] = 'test-internal-token';
$_SERVER['INTERNAL_API_TOKEN'] = 'test-internal-token';

$exit = 1;
try {
    ClassLoader::init(handler: new ProcScanHandler());
    /** @var ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';
    ApplicationContext::setContainer($container);

    /** @var CloudAppInstallService $service */
    $service = $container->get(CloudAppInstallService::class);

    try {
        $service->assertPackageUrlAllowed('https://evil.example.com/pkg.zip');
        caiFail('allowlist should reject evil host');
    } catch (\InvalidArgumentException $e) {
        if (! str_contains($e->getMessage(), '白名单')) {
            caiFail('allowlist message: ' . $e->getMessage());
        }
        caiOk('allowlist_reject');
    }

    $unauth = $service->install([
        'site_key' => 'sk',
        'domain' => 'https://tenant.example.com',
        'app_identifier' => 'vendor/fixture-app',
        'version' => '1.0.0',
        'package_url' => "http://127.0.0.1:{$port}/pkg.zip",
        'package_hash' => $goodHash,
    ]);
    if (($unauth['ok'] ?? true) !== false || ! str_contains((string) ($unauth['message'] ?? ''), '未授权')) {
        caiFail('unauthorized expected, got: ' . json_encode($unauth, JSON_UNESCAPED_UNICODE));
    }
    caiOk('unauthorized_reject');

    file_put_contents($modeFile, 'allow');

    $badHash = $service->install([
        'site_key' => 'sk',
        'domain' => 'https://tenant.example.com',
        'app_identifier' => 'vendor/fixture-app',
        'version' => '1.0.0',
        'package_url' => "http://127.0.0.1:{$port}/pkg.zip",
        'package_hash' => str_repeat('0', 64),
    ]);
    if (($badHash['ok'] ?? true) !== false || ! str_contains((string) ($badHash['message'] ?? ''), 'hash')) {
        caiFail('wrong hash expected, got: ' . json_encode($badHash, JSON_UNESCAPED_UNICODE));
    }
    caiOk('wrong_hash_reject');

    putenv('CLOUD_APP_INSTALL_ALLOW_SKIP=true');
    $_ENV['CLOUD_APP_INSTALL_ALLOW_SKIP'] = 'true';
    $_SERVER['CLOUD_APP_INSTALL_ALLOW_SKIP'] = 'true';

    $ok = $service->install([
        'site_key' => 'sk',
        'domain' => 'https://tenant.example.com',
        'app_identifier' => 'vendor/fixture-app',
        'version' => '1.0.0',
        'package_url' => "http://127.0.0.1:{$port}/pkg.zip",
        'package_hash' => $goodHash,
        '_skip_plugin_install' => true,
    ]);
    if (($ok['ok'] ?? false) !== true || ($ok['message'] ?? '') !== 'hash_ok_skip_install') {
        caiFail('happy hash chain expected, got: ' . json_encode($ok, JSON_UNESCAPED_UNICODE));
    }
    caiOk('hash_chain_skip_install');

    echo "PASS\n";
    echo "NOTE: real Plugin::install SKIP (no Task 6 e2e)\n";
    $exit = 0;
} catch (\Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . "\n";
    $exit = 1;
} finally {
    if (isset($proc) && is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
    if (isset($router)) {
        @unlink($router);
    }
    if (isset($modeFile)) {
        @unlink($modeFile);
    }
    @unlink($fixtureCopy);
}

exit($exit);
