<?php

declare(strict_types=1);

/**
 * 重建站点设置菜单（对齐微擎：云服务 / 设置 / 常用工具 / 后台任务）。
 * 幂等：先删 setting* 再插入；并尽量把新菜单挂回原角色。
 * 若结构已存在，仅把仍指向 placeholder 的云服务菜单改到真实页面。
 *
 * Usage: php scripts/ensure-setting-menu.php
 *        php scripts/ensure-setting-menu.php --force
 */
$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$force = in_array('--force', $argv ?? [], true);

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

$cloudPages = [
    'setting:cloud:upgrade' => [
        'component' => 'base/views/setting/cloud/upgrade/index',
        'path' => '/setting/cloud/upgrade',
        'title' => '系统升级',
        'icon' => 'ri:refresh-line',
        'parent' => 'setting:cloud',
        'sort' => 10,
    ],
    'setting:cloud:register' => [
        'component' => 'base/views/setting/cloud/register/index',
        'path' => '/setting/cloud/register',
        'title' => '注册站点',
        'icon' => 'ri:registered-line',
        'parent' => 'setting:cloud',
        'sort' => 20,
    ],
    'setting:cloud-diagnose' => [
        'component' => 'base/views/setting/cloud/diagnose/index',
        'path' => '/setting/cloud-diagnose',
        'title' => '云服务诊断',
        'icon' => 'ri:cloud-line',
        'parent' => 'setting:cloud',
        'sort' => 30,
    ],
    'setting:cloud:store' => [
        'component' => 'base/views/setting/cloud/store/index',
        'path' => '/setting/cloud/store',
        'title' => '应用管理',
        'icon' => 'ri:store-2-line',
        'parent' => null,
        'sort' => 999,
    ],
];

$patchCloudComponents = static function (PDO $pdo, array $cloudPages): int {
    $update = $pdo->prepare('UPDATE menu SET component = ?, path = ?, updated_at = ? WHERE name = ?');
    $insert = $pdo->prepare(
        'INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, sort, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?)'
    );
    $patched = 0;
    $now = date('Y-m-d H:i:s');
    foreach ($cloudPages as $name => $cfg) {
        $component = $cfg['component'];
        $cur = $pdo->prepare('SELECT id, component FROM menu WHERE name = ? LIMIT 1');
        $cur->execute([$name]);
        $row = $cur->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ((string) $row['component'] === $component) {
                continue;
            }
            $update->execute([$component, $cfg['path'], $now, $name]);
            ++$patched;
            echo "patched {$name}: {$row['component']} -> {$component}\n";
            continue;
        }
        $parentId = 0;
        if ($cfg['parent'] !== null) {
            $parentId = (int) $pdo->query(
                'SELECT id FROM menu WHERE name=' . $pdo->quote($cfg['parent']) . ' LIMIT 1'
            )->fetchColumn();
            if ($parentId <= 0) {
                echo "skip insert {$name}: parent {$cfg['parent']} missing\n";
                continue;
            }
        }
        $meta = json_encode([
            'title' => $cfg['title'],
            'i18n' => 'menu.' . $name,
            'icon' => $cfg['icon'],
            'type' => 'M',
            'hidden' => 0,
            'componentPath' => 'modules/',
            'componentSuffix' => '.vue',
            'breadcrumbEnable' => 1,
            'copyright' => 1,
            'cache' => 0,
            'affix' => 0,
        ], JSON_UNESCAPED_UNICODE);
        $insert->execute([
            $parentId,
            $name,
            $cfg['path'],
            $component,
            '',
            '',
            $meta,
            $cfg['sort'],
            $now,
            $now,
        ]);
        ++$patched;
        echo "inserted {$name} -> {$component}\n";
    }

    return $patched;
};

$exists = (bool) $pdo->query("SELECT id FROM menu WHERE name='setting' LIMIT 1")->fetchColumn();
if ($exists && ! $force) {
    // 检测是否已是新结构（有 setting:cloud 分组）
    $hasCloud = (bool) $pdo->query("SELECT id FROM menu WHERE name='setting:cloud' LIMIT 1")->fetchColumn();
    if ($hasCloud) {
        $n = $patchCloudComponents($pdo, $cloudPages);
        echo $n > 0
            ? "patched {$n} cloud menu component(s)\n"
            : "setting menu already restructured (use --force to rebuild)\n";
        exit(0);
    }
    echo "old setting menu detected, restructuring...\n";
}

$now = date('Y-m-d H:i:s');

// 收集曾绑定这些菜单的角色，重建后重新挂上
$roleIds = $pdo->query(
    "SELECT DISTINCT rbm.role_id
     FROM role_belongs_menu rbm
     INNER JOIN menu m ON m.id = rbm.menu_id
     WHERE m.name = 'setting' OR m.name LIKE 'setting:%'"
)->fetchAll(PDO::FETCH_COLUMN);

$pdo->beginTransaction();
try {
    $ids = $pdo->query(
        "SELECT id FROM menu WHERE name = 'setting' OR name LIKE 'setting:%'"
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $pdo->exec("DELETE FROM role_belongs_menu WHERE menu_id IN ({$in})");
        $pdo->exec("DELETE FROM menu WHERE id IN ({$in})");
    }

    $insert = $pdo->prepare(
        'INSERT INTO menu (parent_id, name, path, component, redirect, created_by, updated_by, remark, meta, sort, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?)'
    );

    $metaM = static function (string $title, string $icon = '', bool $withComponent = true, int $cache = 1, string $name = ''): string {
        $meta = [
            'title' => $title,
            'type' => 'M',
            'hidden' => 0,
            'breadcrumbEnable' => 1,
            'copyright' => 1,
            'cache' => $cache,
            'affix' => 0,
        ];
        if ($icon !== '') {
            $meta['icon'] = $icon;
        }
        if ($withComponent) {
            $meta['componentPath'] = 'modules/';
            $meta['componentSuffix'] = '.vue';
        }
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
        PDOStatement $insert,
        int $parentId,
        string $name,
        string $path,
        string $component,
        string $redirect,
        string $meta,
        int $sort,
        string $now,
    ) use ($pdo): int {
        $insert->execute([$parentId, $name, $path, $component, $redirect, '', $meta, $sort, $now, $now]);

        return (int) $pdo->lastInsertId();
    };

    $rootId = $insertMenu(
        $insert,
        0,
        'setting',
        '/setting',
        '',
        '/setting/cloud/upgrade',
        $metaM('站点设置', 'ri:settings-3-line', false),
        90,
        $now
    );

    // 1. 云服务
    $cloudId = $insertMenu($insert, $rootId, 'setting:cloud', '', '', '', $metaM('云服务', 'ri:cloud-line', false), 10, $now);
    $insertMenu($insert, $cloudId, 'setting:cloud:upgrade', '/setting/cloud/upgrade', 'base/views/setting/cloud/upgrade/index', '', $metaM('系统升级', 'ri:refresh-line', true, 0), 10, $now);
    $insertMenu($insert, $cloudId, 'setting:cloud:register', '/setting/cloud/register', 'base/views/setting/cloud/register/index', '', $metaM('注册站点', 'ri:registered-line', true, 0), 20, $now);
    $insertMenu($insert, $cloudId, 'setting:cloud-diagnose', '/setting/cloud-diagnose', 'base/views/setting/cloud/diagnose/index', '', $metaM('云服务诊断', 'ri:cloud-line', true, 0), 30, $now);

    // 2. 设置
    $groupId = $insertMenu($insert, $rootId, 'setting:group', '', '', '', $metaM('设置', 'ri:settings-4-line', false), 20, $now);

    $apiButtons = [
        'setting:site' => '站点设置',
        'setting:attachment' => '附件设置',
        'setting:systeminfo' => '系统信息',
        'setting:user-login' => '用户登录/注册设置',
    ];

    $pages = [
        ['setting:site', '/setting/site', 'base/views/setting/site/index', '站点设置', 'ri:global-line', 10],
        ['setting:attachment', '/setting/attachment', 'base/views/setting/attachment/index', '附件设置', 'ri:attachment-2', 20],
        ['setting:systeminfo', '/setting/systeminfo', 'base/views/setting/systeminfo/index', '系统信息', 'ri:information-line', 30],
        ['setting:user-login', '/setting/user-login', 'base/views/setting/user-login/index', '用户登录/注册设置', 'ri:user-settings-line', 40],
    ];

    foreach ($pages as [$code, $path, $component, $title, $icon, $sort]) {
        $pageName = array_key_exists($code, $apiButtons) ? $code . ':page' : $code;
        $pageId = $insertMenu($insert, $groupId, $pageName, $path, $component, '', $metaM($title, $icon, true, 1, $pageName), $sort, $now);
        if (isset($apiButtons[$code])) {
            $insertMenu($insert, $pageId, $code, '', '', '', $metaB($apiButtons[$code]), 0, $now);
        }
    }

    // 3. 常用工具
    $toolsId = $insertMenu($insert, $rootId, 'setting:tools', '', '', '', $metaM('常用工具', 'ri:tools-line', false, 1, 'setting:tools'), 30, $now);
    $toolSort = 10;
    $insertMenu($insert, $toolsId, 'setting:tools:database', '/setting/tools/database', 'base/views/setting/tools/database/index', '', $metaM('数据库', 'ri:database-2-line', true, 0, 'setting:tools:database'), $toolSort, $now);
    $toolSort += 10;
    $insertMenu($insert, $toolsId, 'setting:system-check', '/system/check', 'base/views/system/check/index', '', $metaM('系统常规检测', 'ri:health-book-line', true, 1, 'setting:system-check'), $toolSort, $now);

    // 「后台任务」由 crontab 插件安装时创建，避免空分组点击 404

    // 应用管理（顶级）
    $insertMenu($insert, 0, 'setting:cloud:store', '/setting/cloud/store', 'base/views/setting/cloud/store/index', '', $metaM('应用管理', 'ri:store-2-line', true, 0, 'setting:cloud:store'), 999, $now);

    // 若无历史角色，挂到 SuperAdmin（若存在）
    if (! $roleIds) {
        $super = $pdo->query("SELECT id FROM role WHERE code='SuperAdmin' OR name='超级管理员' LIMIT 1")->fetchColumn();
        if ($super) {
            $roleIds = [(int) $super];
        }
    }

    if ($roleIds) {
        $newIds = $pdo->query(
            "SELECT id FROM menu WHERE name = 'setting' OR name LIKE 'setting:%'"
        )->fetchAll(PDO::FETCH_COLUMN);
        $bind = $pdo->prepare('INSERT INTO role_belongs_menu (role_id, menu_id) VALUES (?, ?)');
        foreach ($roleIds as $roleId) {
            foreach ($newIds as $menuId) {
                $bind->execute([(int) $roleId, (int) $menuId]);
            }
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$count = (int) $pdo->query("SELECT COUNT(*) FROM menu WHERE name='setting' OR name LIKE 'setting:%'")->fetchColumn();
echo "setting menus restructured ({$count} rows)\n";
