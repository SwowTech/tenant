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

    /** 无宿主注入时，经 apps/host_env.php 读 user-php/.env */
    private static function loadEnvFallback(): void
    {
        $helper = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'host_env.php';
        if (is_file($helper)) {
            require_once $helper;
            if (function_exists('mine_apps_load_host_env')) {
                mine_apps_load_host_env();
            }
        }
    }
}
