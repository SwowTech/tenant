<?php
declare(strict_types=1);

$base = 'http://127.0.0.1:9501';
$failed = 0;

function request(string $url, array $opts = []): array
{
    $ctx = stream_context_create([
        'http' => array_merge([
            'method' => 'GET',
            'ignore_errors' => true,
        ], $opts),
    ]);
    $raw = (string) file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = (int) $m[0];
    }
    return ['status' => $status, 'body' => json_decode($raw, true), 'raw' => $raw];
}

function fail(string $msg): void
{
    global $failed;
    fwrite(STDERR, "[FAIL] {$msg}\n");
    ++$failed;
}

function ok(string $msg): void
{
    echo "[OK] {$msg}\n";
}

$login = request($base . '/admin/passport/login', [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\n",
    'content' => json_encode(['username' => 'admin', 'password' => '123456']),
]);
if ($login['status'] !== 200) {
    fail("login HTTP {$login['status']}");
    exit(1);
}
$token = $login['body']['data']['access_token'] ?? '';
if ($token === '') {
    fail('login: missing access_token');
    exit(1);
}
ok('login');

$authHeader = ['header' => "Authorization: Bearer {$token}\r\n"];

$routes = [
    '/admin/welcome/overview',
    '/admin/welcome/chart?type=realtime&start=2026-07-10&end=2026-07-16',
    '/admin/welcome/version/check',
    '/admin/welcome/saas-bind',
    '/admin/welcome/system-check',
    '/admin/welcome/market/apps',
    '/admin/welcome/market/stats',
];

foreach ($routes as $path) {
    $res = request($base . $path, $authHeader);
    $code = $res['body']['code'] ?? null;
    if ($res['status'] !== 200) {
        fail("{$path} HTTP {$res['status']}");
        continue;
    }
    if ($code !== 200) {
        fail("{$path} code={$code}");
        continue;
    }
    ok("{$path} code=200");
}

$check = request($base . '/admin/welcome/system-check', $authHeader);
$data = $check['body']['data'] ?? [];
$items = $data['items'] ?? [];
$checkNum = (int) ($data['check_num'] ?? -1);
$wrongNum = (int) ($data['check_wrong_num'] ?? -1);
$actualWrong = 0;
foreach ($items as $item) {
    if (! ($item['ok'] ?? false)) {
        ++$actualWrong;
    }
}
if ($checkNum !== count($items)) {
    fail("system-check check_num={$checkNum} but items count=" . count($items));
} else {
    ok("system-check check_num={$checkNum} matches items");
}
if ($wrongNum !== $actualWrong) {
    fail("system-check check_wrong_num={$wrongNum} but failed items={$actualWrong}");
} else {
    ok("system-check check_wrong_num={$wrongNum} matches failed items");
}

if ($failed > 0) {
    fwrite(STDERR, "\n{$failed} check(s) failed\n");
    exit(1);
}

echo "\nAll welcome API checks passed.\n";
exit(0);
