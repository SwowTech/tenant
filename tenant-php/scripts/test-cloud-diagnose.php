<?php

declare(strict_types=1);

/**
 * Smoke test for CloudDiagnoseService (diagnose / ping / reset).
 * Optional E2E: reset then rebind same APP_URL must issue a new site key (saas+user up).
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Support\AppUrl;
use App\Service\Cloud\CloudDiagnoseService;
use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\Context\ApplicationContext;

function diagSkip(string $msg): never
{
    echo "SKIP: {$msg}\n";
    exit(0);
}

function diagFail(string $msg): never
{
    echo "FAIL: {$msg}\n";
    exit(1);
}

function diagHttpJson(string $method, string $url, ?array $body = null, array $headers = []): ?array
{
    $headerLines = array_merge(['Content-Type: application/json'], $headers);
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines) . "\r\n",
            'ignore_errors' => true,
            'timeout' => 8,
        ],
    ];
    if ($body !== null) {
        $opts['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    if ($raw === false) {
        return null;
    }

    return json_decode($raw, true);
}

/**
 * @return array{key: string, via: string}
 */
function diagBindSite(string $siteUrl, string $saasHttpBase, string $phone, string $code): array
{
    $bindResp = diagHttpJson('POST', $saasHttpBase . '/bind-phone', [
        'site_url' => $siteUrl,
        'phone' => $phone,
        'code' => $code,
    ]);
    if ($bindResp !== null) {
        if (($bindResp['code'] ?? 0) !== 200 || empty($bindResp['data']['key'])) {
            diagFail('bind-phone HTTP returned error (is user-php HTTP up for push?)');
        }

        return ['key' => (string) $bindResp['data']['key'], 'via' => 'http'];
    }

    $saasRoot = dirname(BASE_PATH) . '/saas-php';
    if (! is_dir($saasRoot) || ! is_file($saasRoot . '/vendor/autoload.php')) {
        diagSkip('saas HTTP unreachable and saas-php source tree unavailable');
    }

    ! defined('SAAS_BASE_PATH') && define('SAAS_BASE_PATH', $saasRoot);
    require $saasRoot . '/vendor/autoload.php';

    $userContainer = ApplicationContext::getContainer();
    \Hyperf\Di\ClassLoader::init(handler: new \Hyperf\Di\ScanHandler\ProcScanHandler());
    $saasContainer = require $saasRoot . '/config/container.php';
    ApplicationContext::setContainer($saasContainer);

    /** @var \App\Service\CloudSiteService $saasService */
    $saasService = $saasContainer->get(\App\Service\CloudSiteService::class);
    try {
        $saasService->sendSmsCode($phone);
        $data = $saasService->bindPhone($siteUrl, $phone, $code);
        $key = (string) ($data['key'] ?? '');
        if ($key === '') {
            diagFail('saas CloudSiteService::bindPhone returned empty key');
        }

        return ['key' => $key, 'via' => 'service'];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '回推用户端') || str_contains($msg, 'Connection refused') || str_contains($msg, 'Not Found')) {
            diagSkip("saas bind push failed (user-php HTTP likely down): {$msg}");
        }
        if (str_contains($msg, 'Base table or view not found') || str_contains($msg, 'platform_cloud_site')) {
            diagSkip("saas DB/table unavailable: {$msg}");
        }
        diagFail("saas CloudSiteService::bindPhone: {$msg}");
    } finally {
        ApplicationContext::setContainer($userContainer);
    }
}

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

/** @var CloudSiteSettingService $site */
$site = ApplicationContext::getContainer()->get(CloudSiteSettingService::class);
/** @var CloudDiagnoseService $diag */
$diag = ApplicationContext::getContainer()->get(CloudDiagnoseService::class);

$plainToken = 'diag-token-' . time();
$siteKey = 'diag-site-key-' . time();
$site->saveFromPush([
    'key' => $siteKey,
    'token' => $plainToken,
    'url' => 'http://127.0.0.1:9501',
    'username' => 'diag-user',
    'phone' => '138****0000',
    'email' => 'diag@example.com',
]);

if ($site->getTokenPlain() !== $plainToken) {
    echo "FAIL: getTokenPlain mismatch\n";
    exit(1);
}

$data = $diag->diagnose();
$requiredTop = ['bound', 'site', 'network', 'register_path'];
foreach ($requiredTop as $k) {
    if (! array_key_exists($k, $data)) {
        echo "FAIL: diagnose missing {$k}\n";
        exit(1);
    }
}
if ($data['bound'] !== true) {
    echo "FAIL: expected bound=true\n";
    exit(1);
}
$siteFields = ['url', 'key', 'token_masked', 'token', 'version', 'username', 'phone', 'email', 'bound_at'];
foreach ($siteFields as $k) {
    if (! array_key_exists($k, $data['site'])) {
        echo "FAIL: diagnose.site missing {$k}\n";
        exit(1);
    }
}
if ($data['site']['token'] !== '') {
    echo "FAIL: diagnose must not expose plain token\n";
    exit(1);
}
if ($data['site']['token_masked'] === '' || str_contains($data['site']['token_masked'], $plainToken)) {
    echo "FAIL: token_masked invalid\n";
    exit(1);
}
if (! isset($data['network']['server_time'], $data['network']['saas'])) {
    echo "FAIL: diagnose.network incomplete\n";
    exit(1);
}
if ($data['register_path'] !== '/setting/cloud/register') {
    echo "FAIL: register_path mismatch\n";
    exit(1);
}

$ping = $diag->pingSaas();
foreach (['ok', 'url', 'latency_ms', 'message'] as $k) {
    if (! array_key_exists($k, $ping)) {
        echo "FAIL: ping missing {$k}\n";
        exit(1);
    }
}
if (! is_bool($ping['ok']) || ! is_int($ping['latency_ms'])) {
    echo "FAIL: ping field types\n";
    exit(1);
}
echo 'ping_ok=' . ($ping['ok'] ? '1' : '0') . ' latency_ms=' . $ping['latency_ms'] . "\n";
if (! $ping['ok']) {
    echo 'ping_message=' . $ping['message'] . "\n";
}

$resetOk = false;
$resetKeptLocal = false;
try {
    $reset = $diag->reset();
    if (($reset['ok'] ?? false) !== true) {
        echo "FAIL: reset response ok!=true\n";
        exit(1);
    }
    if ($site->isBound()) {
        echo "FAIL: expected unbound after reset\n";
        exit(1);
    }
    $resetOk = true;
    echo "reset=ok\n";
} catch (Throwable $e) {
    if (! $site->isBound()) {
        echo "FAIL: saas revoke failed but local was cleared\n";
        exit(1);
    }
    $resetKeptLocal = true;
    echo 'reset_kept_local=1 (' . $e->getMessage() . ")\n";
}

if (! $resetOk && ! $resetKeptLocal) {
    echo "FAIL: reset path not exercised\n";
    exit(1);
}

// ensure clearSite works even if reset already cleared
$site->clearSite();
if ($site->isBound() || $site->getTokenPlain() !== '') {
    echo "FAIL: clearSite incomplete\n";
    exit(1);
}

try {
    $diag->reset();
    echo "FAIL: reset unbound should throw\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo 'reset_unbound_ok=1' . "\n";
}

echo "PASS: cloud diagnose smoke\n";

echo "\n=== E2E: reset then rebind must issue new site key ===\n";

$userHttpBase = AppUrl::publicBase();
$siteUrl = $userHttpBase;
$phone = '13800138000';
$code = '888888';
$saasHttpBase = rtrim((string) \Hyperf\Support\env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/') . '/cloud/passport';

$e2ePing = $diag->pingSaas();
if (! ($e2ePing['ok'] ?? false)) {
    diagSkip('saas HTTP unreachable for reset e2e (' . ($e2ePing['message'] ?? 'ping failed') . ')');
}
echo "saas_ping=ok\n";

if ($site->isBound()) {
    $boundUrl = rtrim((string) $site->getSite()['url'], '/');
    if ($boundUrl !== $siteUrl) {
        echo "clearing stale bind url={$boundUrl}\n";
        $site->clearSite();
    }
}

if (! $site->isBound()) {
    echo "bind site_url={$siteUrl}\n";
    $initial = diagBindSite($siteUrl, $saasHttpBase, $phone, $code);
    echo "initial_bind_via={$initial['via']} key={$initial['key']}\n";
    if (! $site->isBound()) {
        diagFail('cloud.site not bound after initial bind');
    }
}

$oldKey = trim((string) $site->getSite()['key']);
if ($oldKey === '') {
    diagFail('oldKey empty before reset');
}
echo "oldKey={$oldKey}\n";

try {
    $e2eReset = $diag->reset();
} catch (Throwable $e) {
    diagFail('reset failed: ' . $e->getMessage());
}
if (($e2eReset['ok'] ?? false) !== true) {
    diagFail('reset response ok!=true');
}
if ($site->isBound()) {
    diagFail('expected unbound after reset');
}
echo "reset_e2e=ok\n";

echo "rebind site_url={$siteUrl}\n";
$rebind = diagBindSite($siteUrl, $saasHttpBase, $phone, $code);
$newKey = trim($rebind['key']);
echo "rebind_via={$rebind['via']} newKey={$newKey}\n";

if ($newKey === '') {
    diagFail('newKey empty after rebind');
}
if ($newKey === $oldKey) {
    diagFail("rebind after reset must issue new site key (old={$oldKey}, new={$newKey})");
}
if (! $site->isBound() || trim((string) $site->getSite()['key']) !== $newKey) {
    diagFail('cloud.site not updated after rebind push');
}

echo "reset_new_key=ok\n";
echo "PASS: cloud diagnose reset e2e\n";
exit(0);
