<?php

declare(strict_types=1);

namespace TraceApp;

use PDO;
use PDOException;
use RuntimeException;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        self::loadEnvFallback();

        $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
        $port = (string) (getenv('DB_PORT') ?: '3306');
        $user = (string) (getenv('DB_USERNAME') ?: 'root');
        $pass = getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '';
        $name = (string) (getenv('XUNLIAN_DB_NAME') ?: getenv('APP_DB_NAME') ?: getenv('DB_DATABASE') ?: 'mineadmin');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('database connect failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }

    /** CLI 无宿主注入时，从 user-php/.env 读取 DB_*（apps/.../app -> 上 4 级） */
    private static function loadEnvFallback(): void
    {
        if (getenv('DB_PASSWORD') !== false) {
            return;
        }
        $envFile = dirname(__DIR__, 4) . '/.env';
        if (! is_file($envFile)) {
            return;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if ($k !== '' && getenv($k) === false) {
                putenv("{$k}={$v}");
                $_ENV[$k] = $v;
            }
        }
    }
}
