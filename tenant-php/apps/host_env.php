<?php

declare(strict_types=1);

/**
 * 独立应用读库凭据兜底：宿主未注入完整 DB_* 时，解析 user-php/.env。
 * 供 apps/* 在 config / Db 引导阶段 require。
 */
function mine_apps_load_host_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $need = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET'];
    $missing = false;
    foreach ($need as $key) {
        // 密码允许空字符串，但必须已存在键
        if ($key === 'DB_PASSWORD') {
            if (getenv('DB_PASSWORD') === false) {
                $missing = true;
                break;
            }
            continue;
        }
        $v = getenv($key);
        if ($v === false || $v === '') {
            $missing = true;
            break;
        }
    }
    if (! $missing) {
        return;
    }

    // apps/host_env.php → 上一级即 tenant-php / user-php 根
    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (! is_file($envFile)) {
        return;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k === '' || ! preg_match('/\A[A-Z][A-Z0-9_]*\z/', $k)) {
            continue;
        }
        if (getenv($k) !== false) {
            continue;
        }
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}
