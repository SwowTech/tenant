<?php

/**
 * 应用进程标准入口（生产可换成 Hyperf/Swoole server）.
 * 宿主注入: APP_LISTEN=127.0.0.1:port  APP_GATEWAY_SECRET=...
 */
declare(strict_types=1);

$listen = getenv('APP_LISTEN') ?: '127.0.0.1:29100';
$appRoot = dirname(__DIR__);
$public = $appRoot . '/public';
$router = $public . '/index.php';
$php = PHP_BINARY;

$cmd = escapeshellarg($php) . ' -S ' . escapeshellarg($listen)
    . ' -t ' . escapeshellarg($public)
    . ' ' . escapeshellarg($router);

passthru($cmd, $code);
exit($code);
