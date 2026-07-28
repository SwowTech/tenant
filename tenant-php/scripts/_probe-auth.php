<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:9501/mineadmin/demo/api';
$headers = "X-Tenant-Id: 32\r\nContent-Type: application/json\r\n";

function req(string $method, string $url, string $headers, ?string $body = null): array
{
    $ctx = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => $headers,
            'content' => $body,
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    return [$http_response_header[0] ?? '', (string) $raw];
}

$user = 'u' . bin2hex(random_bytes(3));
[$s1, $b1] = req('POST', $base . '/auth/register', $headers, json_encode(['username' => $user, 'password' => 'pass123']));
echo "register: {$s1} {$b1}\n";
$data = json_decode($b1, true);
$token = is_array($data) ? (string) ($data['token'] ?? '') : '';
if ($token === '') {
    fwrite(STDERR, "FAIL no token\n");
    exit(1);
}
[$s2, $b2] = req('GET', $base . '/auth/me', $headers . "Authorization: Bearer {$token}\r\n");
echo "me: {$s2} {$b2}\n";
if (! str_contains($b2, $user)) {
    fwrite(STDERR, "FAIL me\n");
    exit(1);
}
echo "PASS api auth via gateway\n";
