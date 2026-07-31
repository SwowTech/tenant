<?php

/**
 * ZZZCMS 应用进程入口（PHP built-in server）.
 * 宿主注入: APP_LISTEN=127.0.0.1:port
 */
declare(strict_types=1);

$listen = getenv('APP_LISTEN') ?: '127.0.0.1:29100';
$appRoot = dirname(__DIR__);
$router = $appRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'router.php';
$php = PHP_BINARY;

putenv('APP_SITE_PATH=/swowtech/zzzcms');
$_ENV['APP_SITE_PATH'] = '/swowtech/zzzcms';

$cmd = escapeshellarg($php) . ' -S ' . escapeshellarg($listen)
    . ' -t ' . escapeshellarg($appRoot)
    . ' ' . escapeshellarg($router);

passthru($cmd, $code);
exit($code);
