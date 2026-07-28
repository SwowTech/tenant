<?php

declare(strict_types=1);

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-Tenant-Id: 32\r\n",
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);
$url = 'http://127.0.0.1:9501/mineadmin/demo/';
$body = @file_get_contents($url, false, $ctx);
$headers = $http_response_header ?? [];
echo implode("\n", $headers) . "\n---\n";
echo substr((string) $body, 0, 500) . "\n";
