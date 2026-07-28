<?php

declare(strict_types=1);

/**
 * 可选：写入微信公众号菜单（默认安装 db:seed 不含此项，保持初始态）.
 * Usage: php scripts/ensure-wechat-menu.php
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

$now = date('Y-m-d H:i:s');

if ((int) $pdo->query("SELECT id FROM menu WHERE name='wechat' LIMIT 1")->fetchColumn() > 0) {
    echo "wechat menu already exists\n";
    exit(0);
}

$metaM = static function (string $title, string $icon, string $name = ''): string {
    $meta = [
        'title' => $title,
        'icon' => $icon,
        'type' => 'M',
        'hidden' => 0,
        'componentPath' => 'modules/',
        'componentSuffix' => '.vue',
        'breadcrumbEnable' => 1,
        'copyright' => 1,
        'cache' => 1,
        'affix' => 0,
    ];
    if ($name !== '') {
        $meta['i18n'] = 'menu.' . $name;
    }

    return json_encode($meta, JSON_UNESCAPED_UNICODE);
};

$metaB = static function (string $title): string {
    return json_encode([
        'title' => $title,
        'type' => 'B',
        'hidden' => 1,
        'cache' => 1,
        'affix' => 0,
    ], JSON_UNESCAPED_UNICODE);
};

$insert = $pdo->prepare(
    'INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, sort, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?)'
);

$insert->execute([0, 'wechat', '/wechat', '', '/wechat/account', '', $metaM('微信公众号', 'ri:wechat-line', 'wechat'), 0, $now, $now]);
$parentId = (int) $pdo->lastInsertId();

$insert->execute([
    $parentId,
    'wechat:account',
    '/wechat/account',
    'base/views/wechat/account/index',
    '',
    '',
    $metaM('接入配置', 'ri:settings-3-line', 'wechat:account'),
    0,
    $now,
    $now,
]);
$accountId = (int) $pdo->lastInsertId();

foreach ([
    'wechat:account:view' => '查看配置',
    'wechat:account:save' => '保存配置',
] as $name => $title) {
    $insert->execute([$accountId, $name, '', '', '', '', $metaB($title), 0, $now, $now]);
}

$menuIds = $pdo->query("SELECT id FROM menu WHERE name='wechat' OR name LIKE 'wechat:%'")->fetchAll(PDO::FETCH_COLUMN);
$superId = (int) $pdo->query("SELECT id FROM role WHERE code='SuperAdmin' LIMIT 1")->fetchColumn();
if ($superId > 0 && $menuIds) {
    $bind = $pdo->prepare('INSERT IGNORE INTO role_belongs_menu (role_id, menu_id) VALUES (?, ?)');
    foreach ($menuIds as $mid) {
        $bind->execute([$superId, (int) $mid]);
    }
}

echo "wechat menus seeded\n";
echo "PASS: ensure-wechat-menu complete\n";
