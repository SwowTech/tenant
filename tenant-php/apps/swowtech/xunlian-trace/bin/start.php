<?php

/**
 * 溯源防伪应用进程入口。
 * 宿主注入: APP_LISTEN / APP_GATEWAY_SECRET / DB_*
 */
declare(strict_types=1);

$listen = getenv('APP_LISTEN') ?: '127.0.0.1:29100';
$appRoot = dirname(__DIR__);
$public = $appRoot . DIRECTORY_SEPARATOR . 'public';
$router = $public . DIRECTORY_SEPARATOR . 'index.php';
$php = PHP_BINARY;

$cmd = escapeshellarg($php) . ' -S ' . escapeshellarg($listen)
    . ' -t ' . escapeshellarg($public)
    . ' ' . escapeshellarg($router);

passthru($cmd, $code);
exit($code);
