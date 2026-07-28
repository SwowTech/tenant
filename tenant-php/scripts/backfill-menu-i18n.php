<?php

declare(strict_types=1);

/**
 * Backfill meta.i18n = menu.{name} for menus missing i18n.
 * Usage: php scripts/backfill-menu-i18n.php
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

$now = date('Y-m-d H:i:s');

function patchTable(PDO $pdo, string $table, string $now): int
{
    $updated = 0;
    $sql = 'SELECT id, name, meta FROM `' . str_replace('`', '``', $table) . '`';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare(
        'UPDATE `' . str_replace('`', '``', $table) . '` SET meta=?, updated_at=? WHERE id=?'
    );
    foreach ($rows as $row) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $meta = json_decode((string) ($row['meta'] ?? '{}'), true);
        if (! is_array($meta)) {
            $meta = [];
        }
        $type = (string) ($meta['type'] ?? '');
        if (! in_array($type, ['M', 'L', 'I'], true)) {
            continue;
        }
        $expected = 'menu.' . $name;
        if (($meta['i18n'] ?? '') === $expected) {
            continue;
        }
        // Keep existing custom i18n that is already a non-empty different key (e.g. baseMenu.*)
        if (! empty($meta['i18n']) && ! str_starts_with((string) $meta['i18n'], 'menu.')) {
            continue;
        }
        $meta['i18n'] = $expected;
        $stmt->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $now, (int) $row['id']]);
        ++$updated;
    }

    return $updated;
}

$tables = ['menu'];
foreach ($pdo->query("SHOW TABLES LIKE 'cy\\_%\\_menu'")->fetchAll(PDO::FETCH_NUM) as $t) {
    if (preg_match('/^cy_\d+_menu$/', $t[0])) {
        $tables[] = $t[0];
    }
}

$total = 0;
foreach ($tables as $table) {
    $n = patchTable($pdo, $table, $now);
    echo "{$table}: updated={$n}\n";
    $total += $n;
}
echo "DONE total={$total}\n";
