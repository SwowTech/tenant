<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Library\App\AppPath;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * 租户启用 TP6 溯源时，将 suyuan.sql 导入 mineadmin 库，以租户前缀隔离表.
 * 表: {tablePrefix}xlsy_admin, {tablePrefix}xlsy_article, ...
 */
final class XunlianTraceTp6DbProvisioner
{
    public const IDENTIFIER = 'swowtech/xunlian-trace-tp6';

    /**
     * @param string $tablePrefix 租户表前缀，例 cy_33_
     */
    public function provisionForTenant(int $tenantId, string $tablePrefix): void
    {
        if ($tenantId <= 0) {
            throw new RuntimeException('invalid tenant id');
        }

        $sqlFile = AppPath::appDir(self::IDENTIFIER) . '/suyuan.sql';
        if (! is_file($sqlFile)) {
            throw new RuntimeException('缺少 suyuan.sql: ' . $sqlFile);
        }

        $pdo = $this->pdoWithDatabase();

        if ($this->hasCoreTable($pdo, $tablePrefix)) {
            return;
        }

        $raw = (string) file_get_contents($sqlFile);
        // suyuan.sql 表前缀为 xlsy_，替换为 {tablePrefix}xlsy_
        $raw = str_replace('`xlsy_', '`' . $tablePrefix . 'xlsy_', $raw);
        $raw = str_replace(' xlsy_', ' ' . $tablePrefix . 'xlsy_', $raw);

        $this->execSqlDump($pdo, $raw);
    }

    private function hasCoreTable(PDO $pdo, string $tablePrefix): bool
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '" . $tablePrefix . "xlsy_admin'");

            return (bool) ($stmt && $stmt->fetch());
        } catch (Throwable) {
            return false;
        }
    }

    private function execSqlDump(PDO $pdo, string $sql): void
    {
        // 去掉 mysqldump 的 LOCK/UNLOCK TABLES（单条执行且无需锁表）
        // 先统一换行符，否则 $ 锚点在 \r\n 下可能不命中
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = preg_replace('/^\s*LOCK\s+TABLES\s+.*;\s*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/^\s*UNLOCK\s+TABLES\s*;\s*$/m', '', $sql) ?? $sql;
        // 去掉条件注释与纯注释行
        $sql = preg_replace('/^\\s*--.*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/\\/\\*!\\d{5}.*?\\*\\//s', '', $sql) ?? $sql;
        $parts = preg_split('/;\\s*[\\r\\n]+/', $sql) ?: [];
        foreach ($parts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '/*')) {
                continue;
            }
            if (preg_match('/^(USE|CREATE\\s+DATABASE)\\b/i', $stmt) === 1) {
                continue;
            }
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'already exists')) {
                    continue;
                }
                throw new RuntimeException('导入 suyuan.sql 失败: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    private function pdoWithDatabase(): PDO
    {
        $cfg = config('databases.default');
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $db = (string) ($cfg['database'] ?? 'mineadmin');
        $user = (string) ($cfg['username'] ?? 'root');
        $pass = (string) ($cfg['password'] ?? '');
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]);
    }
}
