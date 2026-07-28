<?php

declare(strict_types=1);

/**
 * Standalone migrate wechat_account (no Swow required).
 * Usage: php scripts/ensure-wechat-account.php
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

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS `wechat_account` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `app_id` varchar(64) NOT NULL DEFAULT '',
  `app_secret` varchar(128) NOT NULL DEFAULT '',
  `token` varchar(64) NOT NULL DEFAULT '',
  `encoding_aes_key` varchar(64) NOT NULL DEFAULT '',
  `level` tinyint NOT NULL DEFAULT 1,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES ('2026_07_16_000001_create_wechat_account_table', 1)");

echo "wechat_account ready\n";

// menu seed (idempotent)
$exists = $pdo->query("SELECT id FROM menu WHERE name='wechat' LIMIT 1")->fetchColumn();
if ($exists) {
    echo "wechat menu already exists\n";
    exit(0);
}

$now = date('Y-m-d H:i:s');
$metaParent = json_encode([
    'title' => '微信公众号',
    'icon' => 'ri:wechat-line',
    'type' => 'M',
    'hidden' => 0,
    'componentPath' => 'modules/',
    'componentSuffix' => '.vue',
    'breadcrumbEnable' => 1,
    'copyright' => 1,
    'cache' => 1,
    'affix' => 0,
], JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare('INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, created_at, updated_at) VALUES (0, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)');
$stmt->execute(['wechat', '/wechat', '', '/wechat/account', '', $metaParent, $now, $now]);
$parentId = (int) $pdo->lastInsertId();

$metaAccount = json_encode([
    'title' => '接入配置',
    'icon' => 'ri:settings-3-line',
    'type' => 'M',
    'hidden' => 0,
    'componentPath' => 'modules/',
    'componentSuffix' => '.vue',
    'breadcrumbEnable' => 1,
    'copyright' => 1,
    'cache' => 1,
    'affix' => 0,
], JSON_UNESCAPED_UNICODE);

$stmt->execute(['wechat:account', '/wechat/account', 'base/views/wechat/account/index', '', '', $metaAccount, $now, $now]);
// fix parent_id for account - the prepare used parent 0; update
$accountId = (int) $pdo->lastInsertId();
$pdo->prepare('UPDATE menu SET parent_id=? WHERE id=?')->execute([$parentId, $accountId]);

foreach ([
    'wechat:account:view' => '查看配置',
    'wechat:account:save' => '保存配置',
] as $name => $title) {
    $meta = json_encode([
        'title' => $title,
        'type' => 'B',
        'hidden' => 1,
        'cache' => 1,
        'affix' => 0,
    ], JSON_UNESCAPED_UNICODE);
    $ins = $pdo->prepare('INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)');
    $ins->execute([$accountId, $name, '', '', '', '', $meta, $now, $now]);
}

echo "wechat menus seeded\n";
