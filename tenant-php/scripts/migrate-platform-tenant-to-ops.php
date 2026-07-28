<?php

declare(strict_types=1);

/**
 * Migrate platform.tenant → mineadmin.ops_tenant (idempotent upsert by id).
 * package_id / expired_at are NOT migrated.
 *
 * Usage:
 *   php scripts/migrate-platform-tenant-to-ops.php --dry-run
 *   php scripts/migrate-platform-tenant-to-ops.php
 */

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\"'");
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = (int) ($env['DB_PORT'] ?? 3306);
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$userDb = $env['DB_DATABASE'] ?? 'mineadmin';
$platformDb = $env['PLATFORM_DB_DATABASE'] ?? 'platform_db';

$userPdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $userDb),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$platformPdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $platformDb),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$platformExists = (int) $platformPdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema=" . $platformPdo->quote($platformDb) . " AND table_name='tenant'"
)->fetchColumn();
if ($platformExists === 0) {
    echo "SKIP: platform.tenant already dropped\n";
    $opsCount = (int) $userPdo->query('SELECT COUNT(*) FROM ops_tenant')->fetchColumn();
    echo "ops_tenant rows={$opsCount}\n";
    exit(0);
}

$opsExists = (int) $userPdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema=" . $userPdo->quote($userDb) . " AND table_name='ops_tenant'"
)->fetchColumn();
if ($opsExists === 0) {
    fwrite(STDERR, "FAIL: ops_tenant missing — run user-php migrate first\n");
    exit(1);
}

$rows = $platformPdo->query(
    'SELECT id, code, name, domain, custom_domain, table_prefix, status,
            contact_phone, contact_email, remark, created_at, updated_at
     FROM tenant ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);

$platformCount = count($rows);
echo ($dryRun ? '[dry-run] ' : '') . "platform.tenant rows={$platformCount}\n";

$upsertSql = 'INSERT INTO ops_tenant
    (id, code, name, domain, custom_domain, table_prefix, status,
     contact_phone, contact_email, remark, created_at, updated_at)
 VALUES
    (:id, :code, :name, :domain, :custom_domain, :table_prefix, :status,
     :contact_phone, :contact_email, :remark, :created_at, :updated_at)
 ON DUPLICATE KEY UPDATE
    code=VALUES(code),
    name=VALUES(name),
    domain=VALUES(domain),
    custom_domain=VALUES(custom_domain),
    table_prefix=VALUES(table_prefix),
    status=VALUES(status),
    contact_phone=VALUES(contact_phone),
    contact_email=VALUES(contact_email),
    remark=VALUES(remark),
    updated_at=VALUES(updated_at)';

$upsert = $userPdo->prepare($upsertSql);
$migrated = 0;

if (! $dryRun) {
    $userPdo->beginTransaction();
}

try {
    foreach ($rows as $row) {
        $prefix = (string) ($row['table_prefix'] ?? '');
        if ($prefix === '') {
            $prefix = 'cy_' . (int) $row['id'] . '_';
        }
        $payload = [
            ':id' => (int) $row['id'],
            ':code' => (string) $row['code'],
            ':name' => (string) $row['name'],
            ':domain' => (string) $row['domain'],
            ':custom_domain' => (string) ($row['custom_domain'] ?? ''),
            ':table_prefix' => $prefix,
            ':status' => (int) $row['status'],
            ':contact_phone' => (string) ($row['contact_phone'] ?? ''),
            ':contact_email' => (string) ($row['contact_email'] ?? ''),
            ':remark' => (string) ($row['remark'] ?? ''),
            ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
        ];
        if ($dryRun) {
            echo "  would upsert id={$payload[':id']} code={$payload[':code']} prefix={$payload[':table_prefix']}\n";
        } else {
            $upsert->execute($payload);
        }
        ++$migrated;
    }
    if (! $dryRun) {
        $userPdo->commit();
    }
} catch (Throwable $e) {
    if (! $dryRun && $userPdo->inTransaction()) {
        $userPdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}

$opsCount = (int) $userPdo->query('SELECT COUNT(*) FROM ops_tenant')->fetchColumn();
echo "migrated/upserted={$migrated}\n";
echo "ops_tenant rows={$opsCount}\n";

if ($opsCount < $platformCount) {
    fwrite(STDERR, "FAIL: ops_tenant count ({$opsCount}) < platform migratable ({$platformCount})\n");
    exit(1);
}

echo ($dryRun ? 'PASS: dry-run OK' : 'PASS: migration OK') . "\n";
exit(0);
