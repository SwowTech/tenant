<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

/**
 * 允许一应用绑定多个域名；新增 is_primary 标记主域名（外链/扫码优先）.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ops_app_domain')) {
            return;
        }

        $pdo = Db::connection()->getPdo();
        $indexNames = $pdo->query('SHOW INDEX FROM ops_app_domain')->fetchAll(PDO::FETCH_ASSOC);
        $uniqueNames = [];
        foreach ($indexNames as $row) {
            if ((int) $row['Non_unique'] === 0 && $row['Key_name'] !== 'PRIMARY' && $row['Column_name'] !== 'domain') {
                $uniqueNames[$row['Key_name']] = true;
            }
        }
        foreach (array_keys($uniqueNames) as $name) {
            // 仅丢掉 (tenant_id, identifier) 唯一约束，保留 domain 唯一
            $cols = [];
            foreach ($indexNames as $row) {
                if ($row['Key_name'] === $name) {
                    $cols[] = $row['Column_name'];
                }
            }
            sort($cols);
            if ($cols === ['identifier', 'tenant_id']) {
                $pdo->exec('ALTER TABLE ops_app_domain DROP INDEX `' . str_replace('`', '``', $name) . '`');
            }
        }

        if (! Schema::hasColumn('ops_app_domain', 'is_primary')) {
            Schema::table('ops_app_domain', function (Blueprint $table) {
                $table->unsignedTinyInteger('is_primary')->default(0)->after('scheme')->comment('1=主域名');
            });
        }

        $hasComposite = false;
        foreach ($indexNames as $row) {
            if ($row['Key_name'] === 'ops_app_domain_tenant_identifier_index') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('ops_app_domain', function (Blueprint $table) {
                    $table->index(['tenant_id', 'identifier'], 'ops_app_domain_tenant_identifier_index');
                });
            } catch (Throwable) {
            }
        }

        $rows = $pdo->query(
            'SELECT MIN(id) AS id FROM ops_app_domain GROUP BY tenant_id, identifier'
        )->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('UPDATE ops_app_domain SET is_primary = 1 WHERE id = ?');
        foreach ($rows as $row) {
            $stmt->execute([(int) $row['id']]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ops_app_domain')) {
            return;
        }

        $pdo = Db::connection()->getPdo();
        try {
            $pdo->exec('ALTER TABLE ops_app_domain DROP INDEX `ops_app_domain_tenant_identifier_index`');
        } catch (Throwable) {
        }

        if (Schema::hasColumn('ops_app_domain', 'is_primary')) {
            Schema::table('ops_app_domain', function (Blueprint $table) {
                $table->dropColumn('is_primary');
            });
        }

        $keep = $pdo->query(
            'SELECT MIN(id) AS id FROM ops_app_domain GROUP BY tenant_id, identifier'
        )->fetchAll(PDO::FETCH_COLUMN);
        if ($keep) {
            $in = implode(',', array_map('intval', $keep));
            $pdo->exec("DELETE FROM ops_app_domain WHERE id NOT IN ({$in})");
        }

        Schema::table('ops_app_domain', function (Blueprint $table) {
            $table->unique(['tenant_id', 'identifier'], 'uk_tenant_app');
        });
    }
};
