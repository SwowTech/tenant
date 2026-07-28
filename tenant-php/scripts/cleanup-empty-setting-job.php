<?php

declare(strict_types=1);

/**
 * 清理空的「后台任务」分组（无定时任务子菜单时点击会 404）.
 * Usage: php scripts/cleanup-empty-setting-job.php
 */
$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\"'");
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

$jobId = (int) $pdo->query("SELECT id FROM menu WHERE name='setting:job' LIMIT 1")->fetchColumn();
if ($jobId <= 0) {
    echo "setting:job not found\n";
    exit(0);
}

$child = (int) $pdo->query(
    "SELECT COUNT(*) FROM menu WHERE parent_id={$jobId} OR name LIKE 'plugin:mine-admin:crontab%'"
)->fetchColumn();

if ($child > 0) {
    // 若仅有 crontab 在其它父级，仍把 crontab 挪到 job 下
    $crontabId = (int) $pdo->query(
        "SELECT id FROM menu WHERE name='plugin:mine-admin:crontab' LIMIT 1"
    )->fetchColumn();
    if ($crontabId > 0) {
        $pdo->exec("UPDATE menu SET parent_id={$jobId}, sort=20 WHERE id={$crontabId}");
        echo "moved plugin:mine-admin:crontab under setting:job\n";
    }
    echo "setting:job has children, keep\n";
    exit(0);
}

$pdo->exec("DELETE FROM role_belongs_menu WHERE menu_id={$jobId}");
$pdo->exec("DELETE FROM menu WHERE id={$jobId}");
echo "removed empty setting:job id={$jobId}\n";
echo "PASS: cleanup-empty-setting-job complete\n";
