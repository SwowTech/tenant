<?php

declare(strict_types=1);

/**
 * 幂等：创始人「应用域名」菜单 + 租户「应用域名」设置菜单.
 * Usage: php scripts/ensure-app-domain-menu.php
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
    string $now,
    int $sort = 0
): int {
    $exists = (int) $pdo->query('SELECT id FROM menu WHERE name=' . $pdo->quote($name) . ' LIMIT 1')->fetchColumn();
    if ($exists > 0) {
        return $exists;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, sort, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$parentId, $name, $path, $component, $redirect, '', $meta, $sort, $now, $now]);

    return (int) $pdo->lastInsertId();
};

$bindRoleMenu = $pdo->prepare(
    'INSERT IGNORE INTO role_belongs_menu (role_id, menu_id) VALUES (?, ?)'
);

$menuIds = [];

// —— 创始人：应用域名 ——
$founderId = (int) $pdo->query("SELECT id FROM menu WHERE name='founder' LIMIT 1")->fetchColumn();
if ($founderId > 0) {
    $appDomainId = $insertMenu(
        $pdo,
        $founderId,
        'founder:app-domains',
        '/founder/app-domains',
        'base/views/founder/app-domains/index',
        '',
        $metaM('应用域名', 'ri:links-line', false, 'founder:app-domains'),
        $now,
        20
    );
    $menuIds[] = $appDomainId;
    foreach ([
        'founder:app-domains:list' => '查看应用域名',
        'founder:app-domains:create' => '绑定应用域名',
        'founder:app-domains:update' => '更新应用域名',
        'founder:app-domains:delete' => '解绑应用域名',
    ] as $name => $title) {
        $menuIds[] = $insertMenu($pdo, $appDomainId, $name, '', '', '', $metaB($title), $now);
    }
} else {
    echo "WARN: founder menu missing, skip founder app-domains\n";
}

$founderRoleId = (int) $pdo->query("SELECT id FROM role WHERE code='founder' LIMIT 1")->fetchColumn();
$superId = (int) $pdo->query("SELECT id FROM role WHERE code='SuperAdmin' LIMIT 1")->fetchColumn();

foreach ($menuIds as $mid) {
    if ($founderRoleId > 0 && str_starts_with(
        (string) $pdo->query('SELECT name FROM menu WHERE id=' . (int) $mid)->fetchColumn(),
        'founder:'
    )) {
        $bindRoleMenu->execute([$founderRoleId, $mid]);
    }
    if ($superId > 0) {
        $bindRoleMenu->execute([$superId, $mid]);
    }
}

// —— 移除「设置 → 应用域名」（改由「我的应用」设置弹框管理）——
$removeAppDomainsMenu = static function (PDO $pdo, string $menuTable, ?string $rbmTable = null): int {
    $mt = str_replace('`', '``', $menuTable);
    $cols = $pdo->query("SHOW COLUMNS FROM `{$mt}`")->fetchAll(PDO::FETCH_COLUMN);
    if (! in_array('name', $cols, true)) {
        return 0;
    }
    $pageId = (int) $pdo->query(
        "SELECT id FROM `{$mt}` WHERE name='setting:app-domains' LIMIT 1"
    )->fetchColumn();
    if ($pageId <= 0) {
        return 0;
    }
    if ($rbmTable) {
        $rt = str_replace('`', '``', $rbmTable);
        if ($pdo->query('SHOW TABLES LIKE ' . $pdo->quote($rbmTable))->fetch()) {
            $pdo->exec("DELETE FROM `{$rt}` WHERE menu_id=" . $pageId);
        }
    } else {
        $pdo->exec('DELETE FROM role_belongs_menu WHERE menu_id=' . $pageId);
    }
    $pdo->exec("DELETE FROM `{$mt}` WHERE id=" . $pageId);

    $rootId = (int) $pdo->query("SELECT id FROM `{$mt}` WHERE name='setting' LIMIT 1")->fetchColumn();
    if ($rootId > 0) {
        $redirect = (string) $pdo->query("SELECT redirect FROM `{$mt}` WHERE id={$rootId}")->fetchColumn();
        if ($redirect === '/setting/app-domains') {
            $sitePath = (string) $pdo->query(
                "SELECT path FROM `{$mt}` WHERE name='setting:site' LIMIT 1"
            )->fetchColumn();
            $pdo->prepare("UPDATE `{$mt}` SET redirect=?, updated_at=? WHERE id=?")->execute([
                $sitePath !== '' ? $sitePath : '/setting/site',
                date('Y-m-d H:i:s'),
                $rootId,
            ]);
        }
    }

    return 1;
};

$removed = $removeAppDomainsMenu($pdo, 'menu');
$tenantTables = $pdo->query("SHOW TABLES LIKE 'cy\\_%\\_menu'")->fetchAll(PDO::FETCH_NUM);
$tenantRemoved = 0;
foreach ($tenantTables as $row) {
    $menuTable = (string) $row[0];
    if (! preg_match('/^cy_(\d+)_menu$/', $menuTable, $m)) {
        continue;
    }
    $tid = (int) $m[1];
    if ($removeAppDomainsMenu($pdo, $menuTable, "cy_{$tid}_role_belongs_menu") > 0) {
        echo "removed tenant#{$tid} setting:app-domains\n";
        ++$tenantRemoved;
    }
}

echo "founder app-domain menus seeded=" . count($menuIds) . ", removed setting:app-domains platform={$removed} tenants={$tenantRemoved}\n";
echo "PASS: ensure-app-domain-menu complete\n";