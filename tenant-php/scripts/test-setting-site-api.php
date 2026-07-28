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
echo 'login: ' . ($token !== '' ? 'ok' : json_encode($login, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
if ($token === '') {
    exit(1);
}

$headers = "Authorization: Bearer {$token}\r\n";

$get = json_decode((string) file_get_contents($base . '/admin/setting/site', false, stream_context_create([
    'http' => ['method' => 'GET', 'header' => $headers, 'ignore_errors' => true],
])), true);
echo 'GET site code=' . ($get['code'] ?? '?') . ' closed=' . json_encode($get['data']['closed'] ?? null) . PHP_EOL;

$putBody = json_encode([
    'closed' => true,
    'close_reason' => '维护中',
    'auto_logout' => 30,
    'debug' => false,
    'log_enabled' => true,
    'remote_login_verify' => false,
], JSON_UNESCAPED_UNICODE);

$put = json_decode((string) file_get_contents($base . '/admin/setting/site', false, stream_context_create([
    'http' => [
        'method' => 'PUT',
        'header' => $headers . "Content-Type: application/json\r\n",
        'content' => $putBody,
        'ignore_errors' => true,
    ],
])), true);
echo 'PUT site code=' . ($put['code'] ?? '?') . ' reason=' . ($put['data']['close_reason'] ?? '') . PHP_EOL;

$info = json_decode((string) file_get_contents($base . '/admin/setting/systeminfo', false, stream_context_create([
    'http' => ['method' => 'GET', 'header' => $headers, 'ignore_errors' => true],
])), true);
echo 'GET systeminfo code=' . ($info['code'] ?? '?') . ' version=' . ($info['data']['app_version'] ?? '') . PHP_EOL;

$icp = json_decode((string) file_get_contents($base . '/admin/setting/site/icp', false, stream_context_create([
    'http' => ['method' => 'GET', 'header' => $headers, 'ignore_errors' => true],
])), true);
echo 'GET icp code=' . ($icp['code'] ?? '?') . ' total=' . ($icp['data']['total'] ?? '?') . PHP_EOL;

$ok = ($get['code'] ?? 0) === 200 && ($put['code'] ?? 0) === 200;
exit($ok ? 0 : 1);
