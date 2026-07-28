<?php

declare(strict_types=1);

/**
 * Ensure founder role exists, bind user id=1, seed founder tenant menus.
 * Usage: php scripts/ensure-founder-role.php
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

$roleId = (int) $pdo->query("SELECT id FROM role WHERE code='founder' LIMIT 1")->fetchColumn();
if ($roleId <= 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO role (name, code, status, sort, created_by, updated_by, remark, created_at, updated_at)
         VALUES (?, ?, 1, 0, 0, 0, ?, ?, ?)'
    );
    $stmt->execute(['创始人', 'founder', '主创始人角色', $now, $now]);
    $roleId = (int) $pdo->lastInsertId();
    echo "founder role created id={$roleId}\n";
} else {
    echo "founder role exists id={$roleId}\n";
}

$userId = (int) $pdo->query('SELECT id FROM user WHERE id=1 LIMIT 1')->fetchColumn();
if ($userId === 1) {
    $bound = (int) $pdo->query(
        "SELECT COUNT(*) FROM user_belongs_role WHERE user_id=1 AND role_id={$roleId}"
    )->fetchColumn();
    if ($bound === 0) {
        $pdo->prepare('INSERT INTO user_belongs_role (user_id, role_id) VALUES (1, ?)')->execute([$roleId]);
        echo "bound user id=1 to founder\n";
    } else {
        echo "user id=1 already has founder\n";
    }
} else {
    echo "WARN: user id=1 not found, skip bind\n";
}

$metaM = static function (string $title, string $icon, bool $hidden = false, string $name = ''): string {
    $meta = [
        'title' => $title,
        'icon' => $icon,
        'type' => 'M',
        'hidden' => $hidden ? 1 : 0,
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

$insertMenu = static function (
    PDO $pdo,
    int $parentId,
    string $name,
    string $path,
    string $component,
    string $redirect,
    string $meta,
    string $now
): int {
    $exists = (int) $pdo->query("SELECT id FROM menu WHERE name=" . $pdo->quote($name) . ' LIMIT 1')->fetchColumn();
    if ($exists > 0) {
        return $exists;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)'
    );
    $stmt->execute([$parentId, $name, $path, $component, $redirect, '', $meta, $now, $now]);

    return (int) $pdo->lastInsertId();
};

$parentId = $insertMenu($pdo, 0, 'founder', '/founder', '', '/founder/tenants', $metaM('租户管理', 'ri:user-star-line', false, 'founder'), $now);

// 已存在菜单时同步主菜单标题与 i18n
$founderMeta = $pdo->query("SELECT id, meta FROM menu WHERE name='founder' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($founderMeta) {
    $meta = json_decode((string) $founderMeta['meta'], true) ?: [];
    $changed = false;
    if (($meta['title'] ?? '') !== '租户管理') {
        $meta['title'] = '租户管理';
        $changed = true;
    }
    if (($meta['i18n'] ?? '') !== 'menu.founder') {
        $meta['i18n'] = 'menu.founder';
        $changed = true;
    }
    if ($changed) {
        $pdo->prepare('UPDATE menu SET meta=?, updated_at=? WHERE id=?')->execute([
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            $now,
            (int) $founderMeta['id'],
        ]);
        echo "founder menu title/i18n synced\n";
    }
}
$listId = $insertMenu(
    $pdo,
    $parentId,
    'founder:tenants',
    '/founder/tenants',
    'base/views/founder/tenants/index',
    '',
    $metaM('租户列表', 'ri:building-line', false, 'founder:tenants'),
    $now
);

$menuIds = [$parentId, $listId];
foreach ([
    'founder:tenants:list' => '查看租户',
    'founder:tenants:create' => '创建租户',
    'founder:tenants:update' => '更新租户',
    'founder:tenants:provision' => '重试开通',
] as $name => $title) {
    $menuIds[] = $insertMenu($pdo, $listId, $name, '', '', '', $metaB($title), $now);
}

$bindRoleMenu = $pdo->prepare(
    'INSERT IGNORE INTO role_belongs_menu (role_id, menu_id) VALUES (?, ?)'
);
foreach ($menuIds as $mid) {
    $bindRoleMenu->execute([$roleId, $mid]);
}

$superId = (int) $pdo->query("SELECT id FROM role WHERE code='SuperAdmin' LIMIT 1")->fetchColumn();
if ($superId > 0) {
    foreach ($menuIds as $mid) {
        $bindRoleMenu->execute([$superId, $mid]);
    }
}

echo "founder menus seeded\n";
echo "PASS: ensure-founder-role complete\n";
