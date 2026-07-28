<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:9501';

function request(string $method, string $url, string $headers = '', ?array $body = null): array
{
    $opts = [
        'http' => [
            'method' => $method,
            'header' => $headers,
            'ignore_errors' => true,
        ],
    ];
    if ($body !== null) {
        $opts['http']['header'] .= "Content-Type: application/json\r\n";
        $opts['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    $raw = (string) file_get_contents($url, false, stream_context_create($opts));
    $json = json_decode($raw, true);

    return is_array($json) ? $json : ['code' => 0, 'message' => 'invalid json', 'raw' => $raw];
}

$login = request('POST', $base . '/admin/passport/login', '', [
    'username' => 'admin',
    'password' => '123456',
]);

$token = $login['data']['access_token'] ?? '';
echo 'login: ' . ($token !== '' ? 'ok' : json_encode($login, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
if ($token === '') {
    exit(1);
}

$headers = "Authorization: Bearer {$token}\r\n";

$getPaths = [
    'site' => '/admin/setting/site',
    'site-icp' => '/admin/setting/site/icp',
    'attachment' => '/admin/setting/attachment',
    'systeminfo' => '/admin/setting/systeminfo',
    'ip-whitelist' => '/admin/setting/ip-whitelist',
    'sensitive-word' => '/admin/setting/sensitive-word',
    'user-login' => '/admin/setting/user-login',
    'oauth' => '/admin/setting/oauth',
];

$ok = true;
foreach ($getPaths as $name => $path) {
    $resp = request('GET', $base . $path, $headers);
    $code = $resp['code'] ?? 0;
    echo "GET {$name} code={$code}" . PHP_EOL;
    if ($code !== 200) {
        $ok = false;
        echo '  response: ' . json_encode($resp, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

$siteGet = request('GET', $base . '/admin/setting/site', $headers);
$sitePayload = is_array($siteGet['data'] ?? null) ? $siteGet['data'] : [];
$sitePut = request('PUT', $base . '/admin/setting/site', $headers, $sitePayload);
$putCode = $sitePut['code'] ?? 0;
echo "PUT site code={$putCode}" . PHP_EOL;
if ($putCode !== 200) {
    $ok = false;
    echo '  response: ' . json_encode($sitePut, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

$check = request('GET', $base . '/admin/welcome/system-check', $headers);
$checkCode = $check['code'] ?? 0;
echo "GET system-check code={$checkCode}" . PHP_EOL;
if ($checkCode !== 200) {
    $ok = false;
    echo '  response: ' . json_encode($check, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

exit($ok ? 0 : 1);
