<?php

declare(strict_types=1);

/**
 * Seed 「我的应用」主菜单 into all cy_*_menu tenant tables.
 * Usage: php scripts/ensure-tenant-apps-menu.php
 */

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

$now = date('Y-m-d H:i:s');
$meta = json_encode([
    'title' => '我的应用',
    'i18n' => 'menu.apps:mine',
    'icon' => 'ri:apps-2-line',
    'type' => 'M',
    'hidden' => 0,
    'componentPath' => 'modules/',
    'componentSuffix' => '.vue',
    'breadcrumbEnable' => 1,
    'copyright' => 1,
    'cache' => 1,
    'affix' => 0,
], JSON_UNESCAPED_UNICODE);

$name = 'apps:mine';
$path = '/apps/mine';
$component = 'base/views/apps/mine/index';

$tables = $pdo->query("SHOW TABLES LIKE 'cy\\_%\\_menu'")->fetchAll(PDO::FETCH_NUM);
$done = 0;
foreach ($tables as $row) {
    $menuTable = (string) $row[0];
    if (! preg_match('/^cy_(\d+)_menu$/', $menuTable, $m)) {
        continue;
    }
    $tid = (int) $m[1];
    $roleTable = "cy_{$tid}_role";
    $rbmTable = "cy_{$tid}_role_belongs_menu";

    $exists = (int) $pdo->query(
        'SELECT id FROM `' . str_replace('`', '``', $menuTable) . '` WHERE name=' . $pdo->quote($name) . ' LIMIT 1'
    )->fetchColumn();

    if ($exists > 0) {
        $menuId = $exists;
        $pdo->prepare(
            'UPDATE `' . str_replace('`', '``', $menuTable) . '`
             SET path=?, component=?, redirect=?, status=1, sort=5, meta=?, updated_at=?
             WHERE id=?'
        )->execute([$path, $component, '', $meta, $now, $menuId]);
    } else {
        $pdo->prepare(
            'INSERT INTO `' . str_replace('`', '``', $menuTable) . '`
             (parent_id, name, path, component, redirect, status, sort, created_by, updated_by, remark, meta, created_at, updated_at)
             VALUES (0, ?, ?, ?, ?, 1, 5, 0, 0, ?, ?, ?, ?)'
        )->execute([$name, $path, $component, '', 'tenant-my-apps', $meta, $now, $now]);
        $menuId = (int) $pdo->lastInsertId();
    }

    $roleExists = (bool) $pdo->query(
        'SHOW TABLES LIKE ' . $pdo->quote($roleTable)
    )->fetch();
    if ($roleExists && $menuId > 0) {
        $roleId = (int) $pdo->query(
            'SELECT id FROM `' . str_replace('`', '``', $roleTable) . "` WHERE code='SuperAdmin' LIMIT 1"
        )->fetchColumn();
        if ($roleId > 0) {
            $rbmOk = (bool) $pdo->query(
                'SHOW TABLES LIKE ' . $pdo->quote($rbmTable)
            )->fetch();
            if ($rbmOk) {
                $pdo->prepare(
                    'INSERT IGNORE INTO `' . str_replace('`', '``', $rbmTable) . '` (role_id, menu_id) VALUES (?, ?)'
                )->execute([$roleId, $menuId]);
            }
        }
    }

    echo "tenant#{$tid} menu_id={$menuId}\n";
    ++$done;
}

echo "DONE seeded={$done}\n";
