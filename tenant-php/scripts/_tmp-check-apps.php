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

$rows = $pdo->query('SELECT identifier, version, status FROM cy_33_tenant_installed_app')->fetchAll(PDO::FETCH_ASSOC);
echo "cy_33_tenant_installed_app:\n";
print_r($rows);

$user = $pdo->query('SELECT id, username, password FROM cy_33_user LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
echo "users:\n";
foreach ($user as $u) {
    echo "id={$u['id']} user={$u['username']} hash_len=" . strlen((string) $u['password']) . "\n";
    // try common passwords
    foreach (['123456', 'admin123', 'Admin123', 'password'] as $p) {
        if (password_verify($p, (string) $u['password'])) {
            echo "  password matched: {$p}\n";
        }
    }
}
