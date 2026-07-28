<?php

declare(strict_types=1);

/**
 * PDO backfill: attach local apps (app.json under apps/) to active tenants.
 * Usage: php scripts/backfill-tenant-local-apps.php
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

$localApps = [];
foreach (glob($base . '/apps/*/*/app.json') ?: [] as $file) {
    $dir = dirname($file);
    $app = basename($dir);
    $vendor = basename(dirname($dir));
    $raw = json_decode((string) file_get_contents($file), true);
    $version = is_array($raw) ? (string) ($raw['version'] ?? '1.0.0') : '1.0.0';
    $localApps[] = ['identifier' => $vendor . '/' . $app, 'version' => $version !== '' ? $version : '1.0.0'];
}

if ($localApps === []) {
    echo "No local apps found under apps/\n";
    exit(0);
}

$tenants = $pdo->query('SELECT id, domain, table_prefix FROM ops_tenant WHERE status=1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$now = date('Y-m-d H:i:s');

foreach ($tenants as $t) {
    $id = (int) $t['id'];
    $prefix = $t['table_prefix'] !== '' && $t['table_prefix'] !== null
        ? (string) $t['table_prefix']
        : 'cy_' . $id . '_';
    $table = $prefix . 'tenant_installed_app';
    $exists = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetch();
    if (! $exists) {
        $pdo->exec(
            'CREATE TABLE `' . str_replace('`', '``', $table) . '` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `identifier` varchar(128) NOT NULL,
              `version` varchar(32) NOT NULL DEFAULT \'1.0.0\',
              `status` tinyint NOT NULL DEFAULT 1,
              `config` json DEFAULT NULL,
              `installed_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_identifier` (`identifier`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        echo "tenant#{$id} created table {$table}\n";
    }

    $attached = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO `' . str_replace('`', '``', $table) . '`
         (`identifier`,`version`,`status`,`config`,`installed_at`,`updated_at`)
         VALUES (?,?,1,NULL,?,?)
         ON DUPLICATE KEY UPDATE `version`=VALUES(`version`), `status`=1, `updated_at`=VALUES(`updated_at`)'
    );
    foreach ($localApps as $app) {
        $stmt->execute([$app['identifier'], $app['version'], $now, $now]);
        ++$attached;
    }
    echo "tenant#{$id} {$t['domain']} upserted={$attached}\n";
}

echo "DONE\n";
