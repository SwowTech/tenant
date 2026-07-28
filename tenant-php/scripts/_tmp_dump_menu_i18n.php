<?php

declare(strict_types=1);

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

function listMissing(PDO $pdo, string $table): void
{
    echo "==== {$table} missing i18n (type M) ====\n";
    $sql = 'SELECT id, name, parent_id, meta FROM `' . str_replace('`', '``', $table) . '` ORDER BY parent_id, sort, id';
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $m = json_decode((string) ($r['meta'] ?? '{}'), true) ?: [];
        $type = (string) ($m['type'] ?? '');
        if ($type !== 'M' && $type !== 'L' && $type !== 'I') {
            continue;
        }
        if (! empty($m['i18n'])) {
            continue;
        }
        echo $r['id'] . "\t" . $r['parent_id'] . "\t" . $r['name'] . "\t" . ($m['title'] ?? '') . "\n";
    }
}

listMissing($pdo, 'menu');
$tables = $pdo->query("SHOW TABLES LIKE 'cy\\_%\\_menu'")->fetchAll(PDO::FETCH_NUM);
foreach ($tables as $t) {
    if (! preg_match('/^cy_\d+_menu$/', $t[0])) {
        continue;
    }
    listMissing($pdo, $t[0]);
}
