<?php
declare(strict_types=1);

$base = 'http://127.0.0.1:9501';

function request(string $url, array $opts = []): array
{
    $ctx = stream_context_create(['http' => array_merge(['method' => 'GET', 'ignore_errors' => true], $opts)]);
    $raw = (string) file_get_contents($url, false, $ctx);
    return ['body' => json_decode($raw, true)];
}

$login = request($base . '/admin/passport/login', [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\n",
    'content' => json_encode(['username' => 'admin', 'password' => '123456']),
]);
$token = $login['body']['data']['access_token'] ?? '';
if ($token === '') {
    fwrite(STDERR, "login fail\n");
    exit(1);
}

echo "=== No tenant context ===\n";
$ctx = stream_context_create(['http' => ['header' => "Authorization: Bearer {$token}\r\n", 'ignore_errors' => true]]);
$bind = json_decode((string) file_get_contents($base . '/admin/welcome/saas-bind', false, $ctx), true);
echo 'saas-bind: ' . json_encode($bind['data'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;

echo "\n=== With X-Tenant-Id: 1 ===\n";
$ctx2 = stream_context_create(['http' => ['header' => "Authorization: Bearer {$token}\r\nX-Tenant-Id: 1\r\n", 'ignore_errors' => true]]);
$bind2 = json_decode((string) file_get_contents($base . '/admin/welcome/saas-bind', false, $ctx2), true);
echo 'saas-bind: ' . json_encode($bind2['data'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;

$check2 = json_decode((string) file_get_contents($base . '/admin/welcome/system-check', false, $ctx2), true);
$saasItem = null;
foreach ($check2['data']['items'] ?? [] as $item) {
    if (($item['key'] ?? '') === 'saas_bind') {
        $saasItem = $item;
        break;
    }
}
echo 'saas check item: ' . json_encode($saasItem, JSON_UNESCAPED_UNICODE) . PHP_EOL;

$apps = json_decode((string) file_get_contents($base . '/admin/welcome/market/apps?page=1&page_size=100', false, $ctx), true);
$list = $apps['data']['list'] ?? [];
echo "\nmarket apps count: " . count($list) . ", total=" . ($apps['data']['total'] ?? 0) . PHP_EOL;
if (count($list) > 0) {
    echo 'first app: ' . json_encode($list[0], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
