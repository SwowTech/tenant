<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
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
    $env['DB_PASSWORD'] ?? ''
);

$rows = $pdo->query(
    "SELECT id, parent_id, name, JSON_UNQUOTE(JSON_EXTRACT(meta, '$.title')) AS t, sort
     FROM menu
     WHERE name = 'setting' OR name LIKE 'setting:%'
     ORDER BY parent_id, sort, id"
)->fetchAll(PDO::FETCH_ASSOC);

$byParent = [];
foreach ($rows as $r) {
    $byParent[(int) $r['parent_id']][] = $r;
}

function walk(int $pid, array $byParent, string $indent = ''): void
{
    foreach ($byParent[$pid] ?? [] as $r) {
        echo $indent . $r['t'] . ' (' . $r['name'] . ')' . PHP_EOL;
        walk((int) $r['id'], $byParent, $indent . '  ');
    }
}

walk(0, $byParent);
