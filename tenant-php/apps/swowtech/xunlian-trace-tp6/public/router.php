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

// 网关若误把 /vendor/app 前缀带进内置服务，先剥掉再找静态文件
$sitePath = rtrim((string) (getenv('APP_SITE_PATH') ?: ''), '/');
if ($sitePath !== '' && ($uri === $sitePath || str_starts_with($uri, $sitePath . '/'))) {
    $uri = substr($uri, strlen($sitePath)) ?: '/';
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . ltrim($uri, '/');
    }
}

// 修正 HTTP_HOST：用网关转发的 X-Forwarded-Host
if (! empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

$publicRoot = __DIR__;
$file = $publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $uri);

// 真实静态文件：自行输出（比 return false 交给内置 server 更稳）
if ($uri !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'html', 'htm' => 'text/html; charset=utf-8',
        'js', 'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json', 'map' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'ico' => 'image/x-icon',
        default => 'application/octet-stream',
    };
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);

    return true;
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

// 运维诊断：不进 TP6，直接测 DB_* / PDO（部署后访问 /__diag）
if ($uri === '/__diag') {
    header('Content-Type: application/json; charset=utf-8');
    $hostEnv = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'host_env.php';
    if (is_file($hostEnv)) {
        require_once $hostEnv;
        if (function_exists('mine_apps_load_host_env')) {
            mine_apps_load_host_env();
        }
    }
    $info = [
        'listen' => getenv('APP_LISTEN') ?: null,
        'site_path' => getenv('APP_SITE_PATH') ?: null,
        'db' => [
            'host' => getenv('DB_HOST') ?: null,
            'port' => getenv('DB_PORT') ?: null,
            'database' => getenv('DB_DATABASE') ?: null,
            'username' => getenv('DB_USERNAME') ?: null,
            'password_set' => getenv('DB_PASSWORD') !== false,
        ],
        'headers' => [
            'x_tenant_id' => $_SERVER['HTTP_X_TENANT_ID'] ?? null,
            'x_tenant_prefix' => $_SERVER['HTTP_X_TENANT_PREFIX'] ?? null,
        ],
        'pdo' => null,
        'error' => null,
    ];
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            (string) (getenv('DB_HOST') ?: '127.0.0.1'),
            (string) (getenv('DB_PORT') ?: '3306'),
            (string) (getenv('DB_DATABASE') ?: 'mineadmin'),
        );
        $pdo = new PDO(
            $dsn,
            (string) (getenv('DB_USERNAME') ?: 'root'),
            getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $tid = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
        $like = $tid > 0 ? ('cy_' . $tid . '_xlsy_sys_config') : '%xlsy_sys_config';
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($like));
        $info['pdo'] = [
            'ok' => true,
            'sys_config_table' => $stmt ? $stmt->fetchColumn() : null,
        ];
    } catch (Throwable $e) {
        $info['error'] = $e->getMessage();
        http_response_code(500);
    }
    echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return true;
}

// 捕获致命错误，避免正式环境空 body 的 500
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (! in_array($err['type'], $fatal, true)) {
        return;
    }
    if (! headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'code' => 500,
        'message' => $err['message'],
        'file' => $err['file'],
        'line' => $err['line'],
    ], JSON_UNESCAPED_UNICODE);
});

// 修复 PHP built-in server 的 PATH_INFO，确保 TP6 路由正确解析
$_SERVER['PATH_INFO'] = $uri;
$_SERVER['ORIG_PATH_INFO'] = $uri;

require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';

return true;
