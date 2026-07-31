<?php

declare(strict_types=1);

/**
 * 将已安装记录 swowtech/yiqicms 更名为 swowtech/zzzcms。
 * Usage: php scripts/rename-yiqicms-to-zzzcms.php
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

$from = 'swowtech/yiqicms';
$to = 'swowtech/zzzcms';

/** @var list<array{0:string,1:string,2?:string}> $jobs table, column, optional LIKE */
$jobs = [
    ['tenant_installed_app', 'identifier'],
    ['ops_app_domain', 'identifier'],
    ['app_miniprogram_config', 'app_identifier'],
    ['tenant_app_permission', 'app_identifier'],
];

$likeJobs = [
    ["cy\\_%\\_tenant_installed_app", 'identifier'],
    ["cy\\_%\\_ops_app_domain", 'identifier'],
];

$total = 0;
foreach ($jobs as [$table, $col]) {
    $exists = $pdo->query(
        "SHOW TABLES LIKE '" . str_replace(['%', '_'], ['\\%', '\\_'], $table) . "'"
    )->fetch();
    if (! $exists) {
        echo "skip {$table}\n";
        continue;
    }
    $stmt = $pdo->prepare("UPDATE `{$table}` SET `{$col}` = ? WHERE `{$col}` = ?");
    $stmt->execute([$to, $from]);
    $n = $stmt->rowCount();
    $total += $n;
    echo "{$table}.{$col}: {$n}\n";
}

foreach ($likeJobs as [$like, $col]) {
    $tables = $pdo->query("SHOW TABLES LIKE '{$like}'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {
        $table = (string) $row[0];
        $safe = str_replace('`', '``', $table);
        $stmt = $pdo->prepare("UPDATE `{$safe}` SET `{$col}` = ? WHERE `{$col}` = ?");
        $stmt->execute([$to, $from]);
        $n = $stmt->rowCount();
        $total += $n;
        echo "{$table}.{$col}: {$n}\n";
    }
}

echo "DONE total={$total} {$from} -> {$to}\n";
