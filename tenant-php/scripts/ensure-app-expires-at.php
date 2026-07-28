<?php

declare(strict_types=1);

/**
 * Add expires_at to all cy_*_tenant_installed_app tables.
 * Usage: php scripts/ensure-app-expires-at.php
 */

$base = dirname(__DIR__);
$env = [];
foreach (file($base . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        (int) ($env['DB_PORT'] ?? 3306),
        $env['DB_DATABASE'] ?? 'mineadmin'
    ),
    $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query("SHOW TABLES LIKE 'cy\\_%\\_tenant_installed_app'")->fetchAll(PDO::FETCH_NUM);
$n = 0;
foreach ($tables as $row) {
    $table = (string) $row[0];
    $cols = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . "` LIKE 'expires_at'")->fetch();
    if ($cols) {
        echo "skip {$table}\n";
        continue;
    }
    $pdo->exec(
        'ALTER TABLE `' . str_replace('`', '``', $table) . '`
         ADD COLUMN `expires_at` datetime DEFAULT NULL COMMENT \'授权到期，null=永久\' AFTER `installed_at`'
    );
    echo "altered {$table}\n";
    ++$n;
}
echo "DONE altered={$n}\n";
