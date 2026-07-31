<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Library\App\AppPath;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * ZZZCMS 表装在宿主库，按租户前缀分表：{tenantPrefix}zzz_*（例 cy_33_zzz_language）.
 */
final class ZzzcmsDbProvisioner
{
    public const IDENTIFIER = 'swowtech/zzzcms';

    /**
     * @param string $tablePrefix 租户表前缀，例 cy_33_
     */
    public function provisionForTenant(int $tenantId, string $tablePrefix): void
    {
        if ($tenantId <= 0) {
            throw new RuntimeException('invalid tenant id');
        }

        $pre = self::zzzPrefix($tablePrefix, $tenantId);
        $pdo = $this->pdoWithDatabase();
        if ($this->hasCoreTable($pdo, $pre)) {
            return;
        }

        $pdo->exec("SET SESSION sql_mode=''");
        $this->seedCore($pdo, $pre);

        $schema = AppPath::appDir(self::IDENTIFIER) . '/install/data/mysql.data';
        if (! is_file($schema)) {
            throw new RuntimeException('缺少 mysql.data: ' . $schema);
        }
        $this->execSqlDump($pdo, $this->applyTablePrefix((string) file_get_contents($schema), $pre));

        $seed = AppPath::appDir(self::IDENTIFIER) . '/install/data/test_utf8.data';
        if (is_file($seed)) {
            try {
                $this->execSqlDump($pdo, $this->applyTablePrefix((string) file_get_contents($seed), $pre));
            } catch (Throwable) {
                // 演示数据冲突可忽略
            }
        }

        // 子目录部署：把根路径 /upload|/images 改成 /swowtech/zzzcms/...
        $this->rewritePublicPaths($pdo, $pre);
    }

    /**
     * 演示数据多为 /upload/...；包装路由下需带 SITE_PATH.
     */
    private function rewritePublicPaths(PDO $pdo, string $pre): void
    {
        $site = '/swowtech/zzzcms';
        $safePre = str_replace(['%', '_'], ['\\%', '\\_'], $pre);
        $tables = $pdo->query("SHOW TABLES LIKE '{$safePre}%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $cols = [];
            foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`') as $c) {
                $type = strtolower((string) $c['Type']);
                if (str_contains($type, 'char') || str_contains($type, 'text') || str_contains($type, 'blob')) {
                    $cols[] = $c['Field'];
                }
            }
            foreach ($cols as $col) {
                $t = str_replace('`', '``', $table);
                $c = str_replace('`', '``', $col);
                $pdo->exec("UPDATE `{$t}` SET `{$c}` = REPLACE(`{$c}`, '/swowtech/yiqicms', '{$site}') WHERE `{$c}` LIKE '%/swowtech/yiqicms%'");
                $pdo->exec("UPDATE `{$t}` SET `{$c}` = CONCAT('{$site}', `{$c}`) WHERE `{$c}` LIKE '/upload/%' AND `{$c}` NOT LIKE '{$site}%'");
                $pdo->exec("UPDATE `{$t}` SET `{$c}` = CONCAT('{$site}', `{$c}`) WHERE `{$c}` LIKE '/images/%' AND `{$c}` NOT LIKE '{$site}%'");
                $pdo->exec("UPDATE `{$t}` SET `{$c}` = REPLACE(`{$c}`, '/upload/', '{$site}/upload/') WHERE `{$c}` LIKE '%/upload/%' AND `{$c}` NOT LIKE '%{$site}/upload/%'");
            }
        }
    }

    /** @deprecated 使用 provisionForTenant */
    public function ensureInstalled(): void
    {
        // 兼容旧调用：无租户上下文时不建全局表
    }

    public static function zzzPrefix(string $tablePrefix, int $tenantId = 0): string
    {
        $tablePrefix = trim(str_replace('\\', '/', $tablePrefix));
        if ($tablePrefix === '' && $tenantId > 0) {
            $tablePrefix = 'cy_' . $tenantId . '_';
        }
        if ($tablePrefix === '') {
            throw new RuntimeException('missing tenant table prefix');
        }

        return rtrim($tablePrefix, '_') . '_zzz_';
    }

    private function hasCoreTable(PDO $pdo, string $pre): bool
    {
        try {
            $like = str_replace(['%', '_'], ['\\%', '\\_'], $pre . 'language');
            $stmt = $pdo->query("SHOW TABLES LIKE '{$like}'");

            return (bool) ($stmt && $stmt->fetch());
        } catch (Throwable) {
            return false;
        }
    }

    private function seedCore(PDO $pdo, string $pre): void
    {
        $safe = str_replace('`', '``', $pre);
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$safe}language`(
            `lid` int(11) NOT NULL AUTO_INCREMENT,
            `l_name` varchar(255) DEFAULT NULL,
            `l_path` varchar(255) DEFAULT NULL,
            `l_order` int(11) DEFAULT NULL,
            `l_onoff` int(1) DEFAULT NULL,
            `l_alias` varchar(255) DEFAULT NULL,
            `pctemplate` varchar(255) DEFAULT NULL,
            `waptemplate` varchar(255) DEFAULT NULL,
            `pchtmlpath` varchar(255) DEFAULT NULL,
            `waphtmlpath` varchar(255) DEFAULT NULL,
            `sitetitle` varchar(255) DEFAULT NULL,
            `additiontitle` varchar(255) DEFAULT NULL,
            `sitepclogo` varchar(255) DEFAULT NULL,
            `sitewaplogo` varchar(255) DEFAULT NULL,
            `siteurl` varchar(255) DEFAULT NULL,
            `sitewapurl` varchar(255) DEFAULT NULL,
            `companyname` varchar(255) DEFAULT NULL,
            `companyaddress` varchar(255) DEFAULT NULL,
            `companymappoint` varchar(255) DEFAULT NULL,
            `companypostcode` varchar(255) DEFAULT NULL,
            `companycontact` varchar(255) DEFAULT NULL,
            `companytel` varchar(255) DEFAULT NULL,
            `companymobile` varchar(255) DEFAULT NULL,
            `companyfax` varchar(255) DEFAULT NULL,
            `companyemail` varchar(255) DEFAULT NULL,
            `companyicp` varchar(255) DEFAULT NULL,
            `statisticalcode` longtext,
            `copyright` longtext,
            `sitekeys` longtext,
            `sitedesc` longtext,
            `isdefault` int(11) DEFAULT NULL,
            `qq` varchar(255) DEFAULT NULL,
            `weixin` varchar(255) DEFAULT NULL,
            PRIMARY KEY(`lid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $n = (int) $pdo->query("SELECT COUNT(*) FROM `{$safe}language`")->fetchColumn();
        if ($n === 0) {
            $pdo->exec("INSERT INTO `{$safe}language` (`lid`,`l_name`, `l_path`, `l_order`, `l_onoff`, `l_alias`, `pctemplate`, `waptemplate`, `pchtmlpath`, `waphtmlpath`, `sitetitle`, `additiontitle`, `sitepclogo`, `sitewaplogo`, `siteurl`, `sitewapurl`, `companyname`, `companyaddress`, `companymappoint`, `companypostcode`, `companycontact`, `companytel`, `companymobile`, `companyfax`, `companyemail`, `companyicp`, `statisticalcode`, `copyright`, `sitekeys`, `sitedesc`, `isdefault`, `qq`, `weixin`) VALUES
            (1,'中文', '', 0, 1, 'ch', 'cn2016/', 'cn2016/', 'html/', 'html/', 'ZZZCMS', 'zzzcms', '/images/logo.png', '/images/waplogo.png', '/', '/wap', '公司名', '', '116.404309,39.905589', '', '管理员', '88888888', '13888888888', '', '', '', '', '版权所有', '', '', 1, '', '')");
        }
    }

    private function applyTablePrefix(string $sql, string $pre): string
    {
        $sql = str_replace('`zzz_', '`' . $pre, $sql);
        $sql = preg_replace('/\bINTO\s+zzz_/i', 'INTO ' . $pre, $sql) ?? $sql;
        $sql = preg_replace('/\bFROM\s+zzz_/i', 'FROM ' . $pre, $sql) ?? $sql;
        $sql = preg_replace('/\bUPDATE\s+zzz_/i', 'UPDATE ' . $pre, $sql) ?? $sql;
        $sql = preg_replace('/\bTABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?zzz_/i', 'TABLE $1`' . $pre, $sql) ?? $sql;

        return $sql;
    }

    private function execSqlDump(PDO $pdo, string $sql): void
    {
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\\s*[\\r\\n]+/', $sql) ?: [];
        if (count($parts) <= 1) {
            $parts = preg_split('/;\\s*/', $sql) ?: [];
        }
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
                $msg = $e->getMessage();
                if (str_contains($msg, 'already exists') || str_contains($msg, 'Duplicate')) {
                    continue;
                }
                throw new RuntimeException('导入 ZZZCMS SQL 失败: ' . $msg, 0, $e);
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
