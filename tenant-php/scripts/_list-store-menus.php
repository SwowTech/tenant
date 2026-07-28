<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . '/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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

$rows = $pdo->query(
    "SELECT id, parent_id, name, path, sort, status, meta
     FROM menu
     WHERE name LIKE '%store%'
        OR name LIKE '%app-store%'
        OR name LIKE '%appstore%'
        OR meta LIKE '%商城%'
        OR meta LIKE '%商店%'
     ORDER BY parent_id, sort, id"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo "--- top menus ---\n";
$tops = $pdo->query(
    "SELECT id, name, path, sort, status, meta FROM menu WHERE parent_id=0 ORDER BY sort, id"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($tops as $r) {
    $meta = json_decode((string) $r['meta'], true) ?: [];
    echo sprintf(
        "id=%s sort=%s status=%s name=%s title=%s path=%s\n",
        $r['id'],
        $r['sort'],
        $r['status'],
        $r['name'],
        $meta['title'] ?? '',
        $r['path']
    );
}
