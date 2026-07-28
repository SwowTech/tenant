<?php

declare(strict_types=1);

/**
 * Backfill edition/family on all tenant_installed_app tables from local app.json.
 * Usage: php scripts/backfill-tenant-app-edition-family.php [--dry-run]
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/tests/bootstrap.php';

use App\Library\App\AppManifest;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$env = [];
foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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

/**
 * @return list<string>
 */
function discoverInstalledAppTables(PDO $pdo): array
{
    $tables = [];
    foreach ($pdo->query("SHOW TABLES LIKE 'cy\\_%\\_tenant_installed_app'")->fetchAll(PDO::FETCH_NUM) as $row) {
        $tables[] = (string) $row[0];
    }
    $unprefixed = $pdo->query("SHOW TABLES LIKE 'tenant_installed_app'")->fetch(PDO::FETCH_NUM);
    if ($unprefixed) {
        $tables[] = 'tenant_installed_app';
    }

    sort($tables);

    return array_values(array_unique($tables));
}

function quoteTable(string $table): string
{
    return '`' . str_replace('`', '``', $table) . '`';
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SHOW COLUMNS FROM ' . quoteTable($table) . ' LIKE ?'
    );
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

function tableHasFamilyIndex(PDO $pdo, string $table): bool
{
    foreach ($pdo->query('SHOW INDEX FROM ' . quoteTable($table))->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        if (($idx['Column_name'] ?? '') === 'family') {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function ensureEditionFamilyColumns(PDO $pdo, string $table, bool $dryRun): array
{
    $actions = [];
    $quoted = quoteTable($table);

    if (! tableHasColumn($pdo, $table, 'edition')) {
        $sql = "ALTER TABLE {$quoted} ADD COLUMN `edition` varchar(32) NOT NULL DEFAULT '' COMMENT '档位标识，来自 app.json' AFTER `version`";
        $actions[] = 'add edition';
        if (! $dryRun) {
            $pdo->exec($sql);
        }
    }

    if (! tableHasColumn($pdo, $table, 'family')) {
        $sql = "ALTER TABLE {$quoted} ADD COLUMN `family` varchar(100) NOT NULL DEFAULT '' COMMENT '应用家族，用于聚合展示' AFTER `edition`";
        $actions[] = 'add family';
        if (! $dryRun) {
            $pdo->exec($sql);
        }
    }

    if (! tableHasFamilyIndex($pdo, $table)) {
        $sql = "ALTER TABLE {$quoted} ADD INDEX `tenant_installed_app_family_index` (`family`)";
        $actions[] = 'add family index';
        if (! $dryRun) {
            $pdo->exec($sql);
        }
    }

    return $actions;
}

/**
 * @return array{edition: string, family: string}
 */
function resolveEditionFamily(string $identifier): array
{
    try {
        $meta = AppManifest::editionMeta($identifier);

        return [
            'edition' => (string) ($meta['edition'] ?? ''),
            'family' => (string) ($meta['family'] ?? $identifier),
        ];
    } catch (Throwable) {
        return [
            'edition' => '',
            'family' => $identifier,
        ];
    }
}

$tables = discoverInstalledAppTables($pdo);
if ($tables === []) {
    echo "No tenant_installed_app tables found.\n";
    exit(0);
}

$tablesTouched = 0;
$rowsUpdated = 0;
$rowsSkipped = 0;
$schemaActions = 0;

echo $dryRun ? "DRY-RUN\n" : "APPLY\n";

foreach ($tables as $table) {
    $alterActions = ensureEditionFamilyColumns($pdo, $table, $dryRun);
    if ($alterActions !== []) {
        ++$tablesTouched;
        $schemaActions += count($alterActions);
        echo ($dryRun ? 'would alter ' : 'altered ') . $table . ': ' . implode(', ', $alterActions) . "\n";
    }

    $hasEdition = tableHasColumn($pdo, $table, 'edition');
    $hasFamily = tableHasColumn($pdo, $table, 'family');
    $select = '`id`, `identifier`';
    if ($hasEdition) {
        $select .= ', `edition`';
    }
    if ($hasFamily) {
        $select .= ', `family`';
    }
    $rows = $pdo->query(
        'SELECT ' . $select . ' FROM ' . quoteTable($table)
    )->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare(
        'UPDATE ' . quoteTable($table) . ' SET `edition` = ?, `family` = ? WHERE `id` = ?'
    );

    foreach ($rows as $row) {
        $identifier = (string) ($row['identifier'] ?? '');
        if ($identifier === '') {
            ++$rowsSkipped;
            continue;
        }

        $target = resolveEditionFamily($identifier);
        $currentEdition = (string) ($row['edition'] ?? '');
        $currentFamily = (string) ($row['family'] ?? '');

        if ($currentEdition === $target['edition'] && $currentFamily === $target['family']) {
            ++$rowsSkipped;
            continue;
        }

        if ($dryRun) {
            echo "would update {$table} id={$row['id']} {$identifier}: edition='{$target['edition']}', family='{$target['family']}'\n";
        } else {
            $updateStmt->execute([$target['edition'], $target['family'], (int) $row['id']]);
        }
        ++$rowsUpdated;
    }
}

echo sprintf(
    "SUMMARY mode=%s tables=%d schema_actions=%d rows_updated=%d rows_unchanged=%d\n",
    $dryRun ? 'dry-run' : 'apply',
    count($tables),
    $schemaActions,
    $rowsUpdated,
    $rowsSkipped
);
echo "DONE\n";
