<?php

declare(strict_types=1);

/**
 * Smoke: migrate-platform-tenant-to-ops.php dry-run + idempotent upsert.
 * Usage: php scripts/test-tenant-migration-script.php
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

$script = $basePath . '/scripts/migrate-platform-tenant-to-ops.php';
if (! is_file($script)) {
    echo "FAIL: migrate script missing\n";
    exit(1);
}

$php = PHP_BINARY;
$dry = [];
exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' --dry-run 2>&1', $dry, $dryCode);
$dryOut = implode("\n", $dry);
echo $dryOut . "\n";
if ($dryCode !== 0) {
    echo "FAIL: dry-run exited {$dryCode}\n";
    exit(1);
}
echo "PASS: dry-run\n";

$tenantExists = (int) $platformPdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema=" . $platformPdo->quote($platformDb) . " AND table_name='tenant'"
)->fetchColumn();

$marker = 'mig-test-' . time();
$insertedFixture = false;

if ($tenantExists === 1) {
    $platformPdo->prepare(
        'INSERT INTO tenant (code, name, status, domain, custom_domain, table_prefix,
         contact_phone, contact_email, remark, created_at, updated_at)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute([
        $marker,
        'Migration Smoke',
        $marker,
        '',
        '',
        '',
        '',
        'fixture',
    ]);
    $fixtureId = (int) $platformPdo->lastInsertId();
    $insertedFixture = true;
    echo "fixture inserted platform.tenant id={$fixtureId}\n";

    $run = [];
    exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1', $run, $runCode);
    echo implode("\n", $run) . "\n";
    if ($runCode !== 0) {
        echo "FAIL: migrate exited {$runCode}\n";
        exit(1);
    }

    $ops = $userPdo->prepare('SELECT * FROM ops_tenant WHERE id=?');
    $ops->execute([$fixtureId]);
    $row = $ops->fetch(PDO::FETCH_ASSOC);
    if (! $row || $row['code'] !== $marker) {
        echo "FAIL: ops_tenant missing migrated row\n";
        exit(1);
    }
    if (($row['table_prefix'] ?? '') !== 'cy_' . $fixtureId . '_') {
        echo "FAIL: expected table_prefix cy_{$fixtureId}_\n";
        exit(1);
    }
    echo "PASS: upsert by id OK\n";

    // Idempotent second run
    $run2 = [];
    exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1', $run2, $run2Code);
    if ($run2Code !== 0) {
        echo "FAIL: second migrate exited {$run2Code}\n";
        exit(1);
    }
    $dup = (int) $userPdo->query(
        'SELECT COUNT(*) FROM ops_tenant WHERE code=' . $userPdo->quote($marker)
    )->fetchColumn();
    if ($dup !== 1) {
        echo "FAIL: idempotent upsert produced {$dup} rows\n";
        exit(1);
    }
    echo "PASS: idempotent\n";

    // Cleanup fixture (keep other ops rows)
    $platformPdo->prepare('DELETE FROM tenant WHERE id=?')->execute([$fixtureId]);
    $userPdo->prepare('DELETE FROM ops_tenant WHERE id=?')->execute([$fixtureId]);
    echo "PASS: cleaned fixture id={$fixtureId}\n";
} else {
    echo "SKIP: platform.tenant already dropped — dry-run only\n";
}

// Guard: subscription / package tables must still exist
foreach (['tenant_subscription', 'tenant_package'] as $table) {
    $ok = (int) $platformPdo->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema=" . $platformPdo->quote($platformDb) . " AND table_name=" . $platformPdo->quote($table)
    )->fetchColumn();
    if ($ok !== 1) {
        echo "FAIL: {$table} missing (must NOT be dropped)\n";
        exit(1);
    }
}
echo "PASS: tenant_subscription + tenant_package preserved\n";

echo "PASS: tenant migration script smoke complete\n";
exit(0);
