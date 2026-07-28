<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:9501';

$login = json_decode((string) file_get_contents($base . '/admin/passport/login', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['username' => 'admin', 'password' => '123456']),
        'ignore_errors' => true,
    ],
])), true);

$token = $login['data']['access_token'] ?? '';
echo "login: " . ($token !== '' ? 'ok' : json_encode($login, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
if ($token === '') {
    exit(1);
}

$get = json_decode((string) file_get_contents($base . '/admin/wechat/account', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$token}\r\n",
        'ignore_errors' => true,
    ],
])), true);
echo "get account code=" . ($get['code'] ?? '?') . ' url=' . ($get['data']['callback_url'] ?? '') . PHP_EOL;

$putBody = json_encode([
    'name' => '测试公众号',
    'app_id' => 'wx_test_appid',
    'app_secret' => 'test_secret_123456',
    'token' => 'mineToken',
    'encoding_aes_key' => '',
    'level' => 1,
    'status' => 1,
], JSON_UNESCAPED_UNICODE);

$put = json_decode((string) file_get_contents($base . '/admin/wechat/account', false, stream_context_create([
    'http' => [
        'method' => 'PUT',
        'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
        'content' => $putBody,
        'ignore_errors' => true,
    ],
])), true);
echo "put account code=" . ($put['code'] ?? '?') . ' secret=' . ($put['data']['app_secret'] ?? '') . PHP_EOL;

$tokenWx = 'mineToken';
$ts = '1710000000';
$nonce = 'nonce123';
$tmp = [$tokenWx, $ts, $nonce];
sort($tmp, SORT_STRING);
$signature = sha1(implode($tmp));

$codes = ['default'];
$envFile = dirname(__DIR__) . '/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', (int) ($env['DB_PORT'] ?? 3306), $env['PLATFORM_DB_DATABASE'] ?? 'platform_db'),
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tenantCode = (string) $pdo->query('SELECT code FROM tenant ORDER BY id LIMIT 1')->fetchColumn();
    if ($tenantCode !== '') {
        $codes[] = $tenantCode;
    }
} catch (Throwable) {
    // platform_db 无租户时仅测 default（单库本地）
}

foreach ($codes as $code) {
    $url = $base . '/wechat/callback/' . rawurlencode($code)
        . '?signature=' . $signature
        . '&timestamp=' . $ts
        . '&nonce=' . $nonce
        . '&echostr=hello';
    $cb = (string) file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true]]));
    echo "callback tenant={$code} body={$cb}\n";
}
