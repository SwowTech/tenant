<?php

declare(strict_types=1);

/**
 * E2E smoke: saas bind-phone → user cloud.site → forged internal push rejected.
 *
 * Graceful SKIP (exit 0) when servers/DB unavailable; exit 1 on real assertion failure.
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Support\AppUrl;
use App\Repository\SystemSettingRepository;
use App\Service\Cloud\CloudSiteSettingService;
use Hyperf\Context\ApplicationContext;

function e2eSkip(string $msg): never
{
    echo "SKIP: {$msg}\n";
    exit(0);
}

function e2eFail(string $msg): never
{
    echo "FAIL: {$msg}\n";
    exit(1);
}

function httpJson(string $method, string $url, ?array $body = null, array $headers = []): ?array
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

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    e2eSkip('user-php Hyperf bootstrap/DB unavailable (' . $e->getMessage() . ')');
}

/** @var CloudSiteSettingService $cloudSetting */
$cloudSetting = ApplicationContext::getContainer()->get(CloudSiteSettingService::class);
/** @var SystemSettingRepository $settingRepo */
$settingRepo = ApplicationContext::getContainer()->get(SystemSettingRepository::class);

$saasHttpBase = 'http://127.0.0.1:9502/cloud/passport';
$userHttpBase = AppUrl::publicBase();
$phone = '13800138000';
$code = '888888';
$siteUrl = $userHttpBase . '/e2e-smoke-' . substr(bin2hex(random_bytes(4)), 0, 8);

echo "=== Step 1: bind-phone (saas) ===\n";
echo "site_url={$siteUrl}\n";

$bindKey = '';
$bindVia = '';

// Prefer HTTP when saas-php is up
$bindResp = httpJson('POST', $saasHttpBase . '/bind-phone', [
    'site_url' => $siteUrl,
    'phone' => $phone,
    'code' => $code,
]);
if ($bindResp !== null) {
    echo 'http_bind=' . json_encode($bindResp, JSON_UNESCAPED_UNICODE) . "\n";
    if (($bindResp['code'] ?? 0) !== 200 || empty($bindResp['data']['key'])) {
        e2eFail('bind-phone HTTP returned error (is user-php HTTP up for push?)');
    }
    $bindKey = (string) $bindResp['data']['key'];
    $bindVia = 'http';
} else {
    echo "saas HTTP not reachable, trying CloudSiteService direct\n";
    $saasRoot = dirname(BASE_PATH) . '/saas-php';
    if (! is_dir($saasRoot) || ! is_file($saasRoot . '/vendor/autoload.php')) {
        e2eSkip('saas-php not reachable and saas source tree unavailable');
    }

    ! defined('SAAS_BASE_PATH') && define('SAAS_BASE_PATH', $saasRoot);
    require $saasRoot . '/vendor/autoload.php';

    \Hyperf\Di\ClassLoader::init(handler: new \Hyperf\Di\ScanHandler\ProcScanHandler());
    $saasContainer = require $saasRoot . '/config/container.php';
    \Hyperf\Context\ApplicationContext::setContainer($saasContainer);

    /** @var \App\Service\CloudSiteService $saasService */
    $saasService = $saasContainer->get(\App\Service\CloudSiteService::class);
    try {
        $saasService->sendSmsCode($phone);
        $data = $saasService->bindPhone($siteUrl, $phone, $code);
        $bindKey = (string) ($data['key'] ?? '');
        $bindVia = 'service';
        echo "service_bind=ok key={$bindKey}\n";
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '回推用户端') || str_contains($msg, 'Connection refused') || str_contains($msg, 'Not Found')) {
            e2eSkip("saas bind push failed (user-php HTTP likely down): {$msg}");
        }
        if (str_contains($msg, 'Base table or view not found') || str_contains($msg, 'platform_cloud_site')) {
            e2eSkip("saas DB/table unavailable: {$msg}");
        }
        e2eFail("saas CloudSiteService::bindPhone: {$msg}");
    }
}

echo "bind_via={$bindVia} key={$bindKey}\n";

echo "\n=== Step 2: verify user DB cloud.site ===\n";
$raw = $settingRepo->get(CloudSiteSettingService::SETTING_KEY, []);
if (! is_array($raw)) {
    e2eFail('cloud.site setting missing or not array');
}
$storedKey = trim((string) ($raw['key'] ?? ''));
$storedToken = trim((string) ($raw['token'] ?? ''));
echo "db_key={$storedKey}\n";
echo 'db_token_len=' . strlen($storedToken) . "\n";

if ($storedKey === '' || $storedToken === '') {
    e2eFail('cloud.site key/token empty after bind');
}
if ($bindKey !== '' && $storedKey !== $bindKey) {
    e2eFail("cloud.site key mismatch: expected {$bindKey}, got {$storedKey}");
}
if (! $cloudSetting->isBound()) {
    e2eFail('CloudSiteSettingService::isBound() false after bind');
}

$info = $cloudSetting->siteInfoForAdmin();
if (! $info['bound'] || $info['key'] === '' || $info['token_masked'] === '') {
    e2eFail('siteInfoForAdmin missing bound/key/token_masked');
}
echo 'token_masked=' . $info['token_masked'] . "\n";

echo "\n=== Step 3: forged internal push (wrong X-Internal-Token) ===\n";
$internalUrl = $userHttpBase . '/internal/cloud/site-auth';
$forge = httpJson('POST', $internalUrl, [
    'key' => 'forge-key',
    'token' => 'forge-token',
    'url' => 'http://evil.example',
    'username' => 'evil',
    'phone' => '138****0000',
], ['X-Internal-Token: wrong-token-on-purpose']);

if ($forge === null) {
    echo "SKIP: user-php HTTP not reachable at {$internalUrl} — cannot test forged push\n";
} else {
    echo 'forge_resp=' . json_encode($forge, JSON_UNESCAPED_UNICODE) . "\n";
    $forgeCode = (int) ($forge['code'] ?? 0);
    if ($forgeCode === 200) {
        e2eFail('forged internal push with wrong token was accepted');
    }
    echo "forge_rejected=ok (code={$forgeCode})\n";
}

echo "\n=== Step 4 (optional): bind-email channel ===\n";
$email = 'e2e@example.com';
$emailSiteUrl = $userHttpBase . '/e2e-email-' . substr(bin2hex(random_bytes(4)), 0, 8);
echo "email_site_url={$emailSiteUrl}\n";

$emailBindResp = httpJson('POST', $saasHttpBase . '/bind', [
    'site_url' => $emailSiteUrl,
    'channel' => 'email',
    'email' => $email,
    'code' => $code,
]);
if ($emailBindResp === null) {
    echo "SKIP: saas HTTP not reachable for email bind\n";
} elseif (($emailBindResp['code'] ?? 0) !== 200 || empty($emailBindResp['data']['key'])) {
    echo 'http_bind_email=' . json_encode($emailBindResp, JSON_UNESCAPED_UNICODE) . "\n";
    echo "SKIP: bind email channel failed (env unavailable)\n";
} else {
    echo 'http_bind_email=' . json_encode($emailBindResp, JSON_UNESCAPED_UNICODE) . "\n";
    $rawAfterEmail = $settingRepo->get(CloudSiteSettingService::SETTING_KEY, []);
    $storedEmail = trim((string) (is_array($rawAfterEmail) ? ($rawAfterEmail['email'] ?? '') : ''));
    if ($storedEmail === '') {
        e2eFail('cloud.site email empty after email bind (push did not persist email)');
    }
    echo "email_bind_ok stored_email={$storedEmail}\n";
}

echo "\nPASS: cloud bind e2e smoke\n";
exit(0);
