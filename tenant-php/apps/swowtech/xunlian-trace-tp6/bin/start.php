<?php

/**
 * 溯源防伪 TP6 应用进程入口（PHP built-in server）。
 * 宿主注入: APP_LISTEN=127.0.0.1:port
 */
declare(strict_types=1);

$listen = getenv('APP_LISTEN') ?: '127.0.0.1:29120';
$appRoot = dirname(__DIR__);
$public = $appRoot . DIRECTORY_SEPARATOR . 'public';
$router = $public . DIRECTORY_SEPARATOR . 'router.php';
$php = PHP_BINARY;

putenv('APP_SITE_PATH=/swowtech/xunlian-trace-tp6');
$_ENV['APP_SITE_PATH'] = '/swowtech/xunlian-trace-tp6';

if (! is_dir($appRoot . DIRECTORY_SEPARATOR . 'runtime')) {
    @mkdir($appRoot . DIRECTORY_SEPARATOR . 'runtime', 0755, true);
}

$cmd = escapeshellarg($php) . ' -S ' . escapeshellarg($listen)
    . ' -t ' . escapeshellarg($public)
    . ' ' . escapeshellarg($router);

passthru($cmd, $code);
exit($code);
