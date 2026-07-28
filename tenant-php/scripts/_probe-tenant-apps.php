<?php
$base = dirname(__DIR__);
$env = [];
foreach (file($base . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', (int)($env['DB_PORT'] ?? 3306), $env['DB_DATABASE'] ?? 'mineadmin'),
    $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$tenants = $pdo->query('SELECT id,name,domain,status,table_prefix FROM ops_tenant ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
foreach ($tenants as $t) {
    $prefix = $t['table_prefix'] ?: ('cy_' . $t['id'] . '_');
    $table = $prefix . 'tenant_installed_app';
    $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetch();
    echo "tenant#{$t['id']} {$t['domain']} status={$t['status']} table={$table} exists=" . ($exists ? 'Y' : 'N') . PHP_EOL;
    if ($exists) {
        $rows = $pdo->query('SELECT identifier,status FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
        echo '  apps=' . json_encode($rows, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
$appsDir = $base . '/apps';
if (is_dir($appsDir)) {
    foreach (glob($appsDir . '/*/*/app.json') ?: [] as $f) {
        echo 'local app: ' . dirname(str_replace($appsDir . '/', '', $f)) . PHP_EOL;
    }
}
