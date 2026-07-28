<?php
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
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'], (int) $env['DB_PORT'], $env['DB_DATABASE']),
    $env['DB_USERNAME'],
    $env['DB_PASSWORD']
);
foreach ($pdo->query("SELECT name, JSON_UNQUOTE(JSON_EXTRACT(meta,'$.title')) t, JSON_UNQUOTE(JSON_EXTRACT(meta,'$.i18n')) i FROM menu WHERE parent_id=0 ORDER BY sort") as $r) {
    echo $r['name'] . ' | ' . $r['t'] . ' | ' . $r['i'] . PHP_EOL;
}
