<?php

declare(strict_types=1);

/**
 * 将已打好的 xunlian-trace-tp6 zip 发布到本地 saas 市场并审核通过。
 *
 * Usage:
 *   php scripts/publish-xunlian-trace-tp6-to-saas.php
 *   php scripts/publish-xunlian-trace-tp6-to-saas.php --zip=path/to.zip
 */
$basePath = dirname(__DIR__);
$saasEnvFile = dirname($basePath) . '/saas-php/.env';
$defaultZip = $basePath . '/runtime/dist/swowtech-xunlian-trace-tp6-1.0.0.zip';
$zip = $defaultZip;
$baseUrl = 'http://127.0.0.1:9502';
$username = 'admin';
$password = '123456';
$tenantId = 1;
$categoryId = 36; // 物联网与人智

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--zip=')) {
        $zip = substr($arg, 6);
    } elseif (str_starts_with($arg, '--url=')) {
        $baseUrl = rtrim(substr($arg, 6), '/');
    } elseif (str_starts_with($arg, '--tenant=')) {
        $tenantId = (int) substr($arg, 9);
    } elseif (str_starts_with($arg, '--category=')) {
        $categoryId = (int) substr($arg, 11);
    }
}

if (! is_file($zip)) {
    fwrite(STDERR, "FAIL: zip not found: {$zip}\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($basePath . '/apps/swowtech/xunlian-trace-tp6/app.json'), true);
if (! is_array($manifest)) {
    fwrite(STDERR, "FAIL: cannot read app.json\n");
    exit(1);
}

function httpJson(string $method, string $url, ?array $json = null, array $headers = []): array
{
    $ch = curl_init($url);
    $hdrs = array_merge(['Accept: application/json'], $headers);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $hdrs,
        CURLOPT_TIMEOUT => 120,
    ];
    if ($json !== null) {
        $body = json_encode($json, JSON_UNESCAPED_UNICODE);
        $hdrs[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $hdrs;
        $opts[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException("curl error: {$err}");
    }
    $data = json_decode($raw, true);
    if (! is_array($data)) {
        throw new RuntimeException("invalid json HTTP {$code}: " . substr($raw, 0, 300));
    }
    $data['_http'] = $code;

    return $data;
}

echo "1) login {$baseUrl}/platform/passport/login\n";
$login = httpJson('POST', $baseUrl . '/platform/passport/login', [
    'username' => $username,
    'password' => $password,
]);
if (($login['code'] ?? 0) !== 200) {
    fwrite(STDERR, 'FAIL login: ' . json_encode($login, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
$token = (string) ($login['data']['access_token'] ?? $login['data']['token'] ?? '');
if ($token === '') {
    fwrite(STDERR, 'FAIL no token: ' . json_encode($login, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
$auth = ['Authorization: Bearer ' . $token];

echo "2) publish\n";
$publish = httpJson('POST', $baseUrl . '/platform/market/publish', [
    'tenant_id' => $tenantId,
    'tenant_code' => 'swowtech',
    'developer_name' => 'SwowTech',
    'identifier' => $manifest['name'],
    'title' => $manifest['title'],
    'version' => $manifest['version'],
    'edition' => $manifest['edition'] ?? 'community',
    'family' => $manifest['family'] ?? $manifest['name'],
    'price_type' => 'free',
    'price' => 0,
    'description' => '溯源防伪（ThinkPHP6 独立应用）',
    'changelog' => '初始上架 ' . $manifest['version'],
    'category_ids' => [$categoryId],
], $auth);
if (($publish['code'] ?? 0) !== 200) {
    fwrite(STDERR, 'FAIL publish: ' . json_encode($publish, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
$app = $publish['data'] ?? [];
$appId = (int) ($app['id'] ?? 0);
$versions = $app['versions'] ?? [];
usort($versions, static fn ($a, $b) => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));
$versionId = (int) ($versions[0]['id'] ?? 0);
if ($appId <= 0 || $versionId <= 0) {
    fwrite(STDERR, 'FAIL missing ids: ' . json_encode($publish, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
echo "   app_id={$appId} version_id={$versionId}\n";

echo "3) upload package (" . round(filesize($zip) / 1048576, 1) . "MB)\n";
$saasStorage = dirname($basePath) . '/saas-php/storage/market/' . $appId;
$localDest = $saasStorage . '/v' . $versionId . '.zip';
$upload = null;
$usedLocal = false;

$ch = curl_init($baseUrl . '/platform/market/versions/' . $versionId . '/package');
$cfile = new CURLFile($zip, 'application/zip', basename($zip));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $auth,
    CURLOPT_POSTFIELDS => ['package' => $cfile],
    CURLOPT_TIMEOUT => 600,
]);
$raw = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($raw !== false && $code === 200) {
    $upload = json_decode($raw, true);
}

if (! is_array($upload) || ($upload['code'] ?? 0) !== 200) {
    // Swow 默认对大包会 413；同机直接写入 saas storage
    echo "   HTTP upload failed (code={$code}), fallback local copy\n";
    if (! is_dir($saasStorage) && ! mkdir($saasStorage, 0775, true) && ! is_dir($saasStorage)) {
        fwrite(STDERR, "FAIL mkdir {$saasStorage}\n");
        exit(1);
    }
    if (! copy($zip, $localDest)) {
        fwrite(STDERR, "FAIL copy to {$localDest}\n");
        exit(1);
    }
    $sha = hash_file('sha256', $localDest);
    $size = filesize($localDest);
    $packageUrl = rtrim($baseUrl, '/') . '/store/packages/' . $versionId;
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=platform_db;charset=utf8mb4',
        'root',
        'rootrootroot123123123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    if (is_file($saasEnvFile)) {
        $env = [];
        foreach (file($saasEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\"'");
        }
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $env['DB_HOST'] ?? '127.0.0.1',
                (int) ($env['DB_PORT'] ?? 3306),
                $env['DB_DATABASE'] ?? 'platform_db'
            ),
            $env['DB_USERNAME'] ?? 'root',
            $env['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    $pdo->prepare('UPDATE market_app_version SET package_url=?, package_hash=?, package_size=? WHERE id=?')
        ->execute([$packageUrl, $sha, $size, $versionId]);
    $pdo->prepare('UPDATE market_app SET edition=?, family=? WHERE id=?')
        ->execute([
            (string) ($manifest['edition'] ?? 'community'),
            (string) ($manifest['family'] ?? $manifest['name']),
            $appId,
        ]);
    $upload = [
        'code' => 200,
        'data' => [
            'package_url' => $packageUrl,
            'package_hash' => $sha,
            'package_size' => $size,
        ],
    ];
    $usedLocal = true;
}

echo '   package_url=' . ($upload['data']['package_url'] ?? '') . ($usedLocal ? ' (local)' : '') . "\n";
echo '   package_hash=' . ($upload['data']['package_hash'] ?? '') . "\n";

echo "4) find pending review\n";
$reviews = httpJson('GET', $baseUrl . '/platform/market/reviews?status=pending&page_size=50', null, $auth);
$reviewId = 0;
foreach (($reviews['data']['list'] ?? []) as $row) {
    if ((int) ($row['app_id'] ?? 0) === $appId && (int) ($row['version_id'] ?? 0) === $versionId) {
        $reviewId = (int) $row['id'];
        break;
    }
}
if ($reviewId <= 0) {
    // fallback: latest review for app
    foreach (($reviews['data']['list'] ?? []) as $row) {
        if ((int) ($row['app_id'] ?? 0) === $appId) {
            $reviewId = (int) $row['id'];
            break;
        }
    }
}
if ($reviewId <= 0) {
    fwrite(STDERR, 'FAIL no pending review: ' . json_encode($reviews, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
echo "   review_id={$reviewId}\n";

echo "5) approve\n";
$approve = httpJson('POST', $baseUrl . '/platform/market/review/' . $reviewId . '/approve', [
    'remark' => 'auto approve xunlian-trace-tp6 pack',
], $auth);
if (($approve['code'] ?? 0) !== 200) {
    fwrite(STDERR, 'FAIL approve: ' . json_encode($approve, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}

$detail = httpJson('GET', $baseUrl . '/platform/market/apps/' . $appId, null, $auth);
echo "PASS published\n";
echo json_encode([
    'app_id' => $appId,
    'version_id' => $versionId,
    'review_id' => $reviewId,
    'identifier' => $manifest['name'],
    'version' => $manifest['version'],
    'status' => $detail['data']['status'] ?? null,
    'edition' => $detail['data']['edition'] ?? null,
    'family' => $detail['data']['family'] ?? null,
    'package_url' => $upload['data']['package_url'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
