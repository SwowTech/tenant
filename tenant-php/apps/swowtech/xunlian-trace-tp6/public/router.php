<?php

declare(strict_types=1);

/**
 * PHP built-in server router for swowtech/xunlian-trace-tp6 (ThinkPHP6).
 * Document root = public/
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

if ($uri === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return true;
}

if (! getenv('APP_SITE_PATH')) {
    putenv('APP_SITE_PATH=/swowtech/xunlian-trace-tp6');
    $_ENV['APP_SITE_PATH'] = '/swowtech/xunlian-trace-tp6';
}

// 修正 HTTP_HOST：用网关转发的 X-Forwarded-Host
if (! empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

$publicRoot = __DIR__;
$file = $publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $uri);

// 真实静态文件交给内置服务器
if ($uri !== '/' && is_file($file)) {
    return false;
}

// SPA / 静态目录 —— 仅对 GET/HEAD/OPTIONS 走 SPA fallback，POST/PUT/DELETE/PATCH 交给 TP6 PHP 路由
$dir = rtrim($file, '/\\');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($uri !== '/' && is_dir($dir) && in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
    foreach (['index.html', 'index.htm', 'index.php'] as $indexName) {
        $index = $dir . DIRECTORY_SEPARATOR . $indexName;
        if (is_file($index)) {
            if (str_ends_with(strtolower($indexName), '.php')) {
                chdir($dir);
                require $index;
                return true;
            }
            header('Content-Type: text/html; charset=utf-8');
            readfile($index);
            return true;
        }
    }
}

// 修复 PHP built-in server 的 PATH_INFO，确保 TP6 路由正确解析
$_SERVER['PATH_INFO'] = $uri;
$_SERVER['ORIG_PATH_INFO'] = $uri;

require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';

return true;
