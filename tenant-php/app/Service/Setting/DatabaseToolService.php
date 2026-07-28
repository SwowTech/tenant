<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Tenant\TenantContext;
use Hyperf\DbConnection\Db;
use Throwable;

/**
 * 系统工具 - 数据库（对齐微擎：备份 / 还原 / 优化）.
 */
final class DatabaseToolService
{
    private const BACKUP_ROOT = '/runtime/db_backup';

    private const ROWS_PER_CHUNK = 200;

    private const VOLUME_BYTES = 2 * 1024 * 1024;

    /**
     * @return list<array<string, mixed>>
     */
    public function listTables(): array
    {
        $database = (string) config('databases.default.database');
        $prefix = $this->activePrefix();
        $rows = Db::select(
            'SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_ROWS AS `rows`,
                    DATA_LENGTH AS data_length, INDEX_LENGTH AS index_length, DATA_FREE AS data_free,
                    TABLE_COLLATION AS collation, CREATE_TIME AS create_time, UPDATE_TIME AS update_time,
                    TABLE_COMMENT AS comment
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME',
            [$database]
        );

        $out = [];
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            if ($name === '' || ! $this->matchPrefix($name, $prefix)) {
                continue;
            }
            $dataFree = (int) ($row->data_free ?? 0);
            $out[] = [
                'name' => $name,
                'engine' => (string) ($row->engine ?? ''),
                'rows' => (int) ($row->rows ?? 0),
                'data' => $this->sizeCount((int) ($row->data_length ?? 0)),
                'index' => $this->sizeCount((int) ($row->index_length ?? 0)),
                'free' => $this->sizeCount($dataFree),
                'free_bytes' => $dataFree,
                'collation' => (string) ($row->collation ?? ''),
                'comment' => (string) ($row->comment ?? ''),
                'need_optimize' => $dataFree > 0,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $tables
     * @return array{optimized: list<string>, skipped: list<string>}
     */
    public function optimize(array $tables): array
    {
        $allowed = array_column($this->listTables(), 'name');
        $allowedMap = array_fill_keys($allowed, true);
        $optimized = [];
        $skipped = [];
        foreach ($tables as $table) {
            $table = trim((string) $table);
            if ($table === '' || ! isset($allowedMap[$table]) || ! $this->safeIdent($table)) {
                $skipped[] = $table;
                continue;
            }
            try {
                Db::statement('OPTIMIZE TABLE `' . str_replace('`', '``', $table) . '`');
                $optimized[] = $table;
            } catch (Throwable) {
                $skipped[] = $table;
            }
        }

        return compact('optimized', 'skipped');
    }

    /**
     * 分卷备份一步（对齐微擎 status/series/index 续传）.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function backupStep(array $state): array
    {
        $tables = $this->listTables();
        $tableNames = array_column($tables, 'name');
        if ($tableNames === []) {
            return ['continue' => false, 'message' => '没有可备份的数据表'];
        }

        $series = max(1, (int) ($state['series'] ?? 1));
        $folder = (string) ($state['folder_suffix'] ?? '');
        if ($folder === '' || preg_match('/[^0-9A-Za-z_-]/', $folder)) {
            $folder = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        }
        $bakDir = $this->backupRoot() . '/' . $folder;
        if (! is_dir($bakDir) && ! mkdir($bakDir, 0755, true) && ! is_dir($bakDir)) {
            throw new BusinessException(ResultCode::FAIL, '无法创建备份目录');
        }

        $volumeSuffix = (string) ($state['volume_suffix'] ?? md5($folder . (string) config('app_name')));
        $lastTable = (string) ($state['last_table'] ?? '');
        $index = (int) ($state['index'] ?? 0);
        $catch = $lastTable === '';
        $dump = '';

        foreach ($tableNames as $table) {
            if ($lastTable !== '' && $table === $lastTable) {
                $catch = true;
            }
            if (! $catch) {
                continue;
            }
            if ($dump !== '') {
                $dump .= "\n\n";
            }
            if ($table !== $lastTable) {
                $dump .= $this->tableSchemaSql($table) . "\n";
                $index = 0;
            }
            while (true) {
                $start = $index * self::ROWS_PER_CHUNK;
                $chunk = $this->tableInsertSql($table, $start, self::ROWS_PER_CHUNK);
                if ($chunk['sql'] !== '') {
                    $dump .= $chunk['sql'];
                    if (strlen($dump) > self::VOLUME_BYTES) {
                        $file = sprintf('%s/volume-%s-%d.sql', $bakDir, $volumeSuffix, $series);
                        file_put_contents($file, $dump . "\n\n");
                        ++$series;
                        ++$index;

                        return [
                            'continue' => true,
                            'message' => '正在导出数据，请勿关闭，当前第 ' . ($series - 1) . ' 卷',
                            'last_table' => $table,
                            'index' => $index,
                            'series' => $series,
                            'folder_suffix' => $folder,
                            'volume_suffix' => $volumeSuffix,
                        ];
                    }
                }
                if ($chunk['count'] < self::ROWS_PER_CHUNK) {
                    break;
                }
                ++$index;
            }
            $lastTable = $table;
            $index = 0;
        }

        $file = sprintf('%s/volume-%s-%d.sql', $bakDir, $volumeSuffix, $series);
        file_put_contents($file, $dump . "\n\n---- SwowTech MySQL Dump End\n");
        $meta = [
            'time' => time(),
            'tables' => count($tableNames),
            'prefix' => $this->activePrefix(),
            'database' => (string) config('databases.default.database'),
        ];
        file_put_contents($bakDir . '/meta.json', json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return [
            'continue' => false,
            'message' => '数据已经备份完成',
            'folder_suffix' => $folder,
            'series' => $series,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBackups(): array
    {
        $root = $this->backupRoot();
        if (! is_dir($root)) {
            return [];
        }
        $dirs = array_filter(scandir($root) ?: [], static fn ($d) => $d !== '.' && $d !== '..');
        $list = [];
        foreach ($dirs as $dir) {
            $path = $root . '/' . $dir;
            if (! is_dir($path)) {
                continue;
            }
            $volumes = glob($path . '/volume-*.sql') ?: [];
            $meta = [];
            $metaFile = $path . '/meta.json';
            if (is_file($metaFile)) {
                $decoded = json_decode((string) file_get_contents($metaFile), true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $mtime = (int) ($meta['time'] ?? filemtime($path) ?: time());
            $list[] = [
                'bakdir' => $dir,
                'time' => $mtime,
                'time_text' => date('Y-m-d H:i:s', $mtime),
                'volume' => count($volumes),
                'prefix' => (string) ($meta['prefix'] ?? ''),
                'size' => $this->sizeCount($this->dirSize($path)),
            ];
        }
        usort($list, static fn ($a, $b) => $b['time'] <=> $a['time']);

        return $list;
    }

    public function deleteBackup(string $dirname): void
    {
        $dirname = $this->assertBackupDir($dirname);
        $path = $this->backupRoot() . '/' . $dirname;
        $this->removeDir($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreStep(string $dirname, int $volumeIndex = 0): array
    {
        $dirname = $this->assertBackupDir($dirname);
        $path = $this->backupRoot() . '/' . $dirname;
        $volumes = glob($path . '/volume-*.sql') ?: [];
        natsort($volumes);
        $volumes = array_values($volumes);
        if ($volumes === []) {
            throw new BusinessException(ResultCode::FAIL, '备份卷不存在');
        }
        if ($volumeIndex < 0 || $volumeIndex >= count($volumes)) {
            return ['continue' => false, 'message' => '成功恢复数据备份'];
        }
        $sql = (string) file_get_contents($volumes[$volumeIndex]);
        $this->execSqlDump($sql);
        $next = $volumeIndex + 1;
        if ($next >= count($volumes)) {
            return ['continue' => false, 'message' => '成功恢复数据备份，建议清理缓存后验证'];
        }

        return [
            'continue' => true,
            'message' => '正在恢复备份，请勿关闭，当前第 ' . $next . ' / ' . count($volumes) . ' 卷',
            'volume_index' => $next,
            'dirname' => $dirname,
        ];
    }

    private function activePrefix(): string
    {
        $tenant = TenantContext::get();
        if ($tenant !== null) {
            return $tenant->tablePrefix;
        }

        return (string) config('databases.default.prefix', '');
    }

    private function matchPrefix(string $table, string $prefix): bool
    {
        if ($prefix === '') {
            return true;
        }

        return str_starts_with($table, $prefix);
    }

    private function safeIdent(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    private function backupRoot(): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $dir = $base . self::BACKUP_ROOT;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function assertBackupDir(string $dirname): string
    {
        $dirname = trim($dirname);
        if ($dirname === '' || preg_match('/[^0-9A-Za-z_-]/', $dirname)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '非法备份目录');
        }
        $path = $this->backupRoot() . '/' . $dirname;
        if (! is_dir($path)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '备份不存在');
        }

        return $dirname;
    }

    private function tableSchemaSql(string $table): string
    {
        $safe = str_replace('`', '``', $table);
        $row = Db::selectOne('SHOW CREATE TABLE `' . $safe . '`');
        $create = '';
        if (is_object($row)) {
            foreach ((array) $row as $v) {
                if (is_string($v) && str_starts_with($v, 'CREATE TABLE')) {
                    $create = $v;
                    break;
                }
            }
        }
        if ($create === '') {
            return '';
        }

        return "DROP TABLE IF EXISTS `{$safe}`;\n" . $create . ";\n";
    }

    /**
     * @return array{sql: string, count: int}
     */
    private function tableInsertSql(string $table, int $offset, int $limit): array
    {
        $safe = str_replace('`', '``', $table);
        try {
            $rows = Db::select('SELECT * FROM `' . $safe . '` LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
        } catch (Throwable) {
            return ['sql' => '', 'count' => 0];
        }
        if ($rows === []) {
            return ['sql' => '', 'count' => 0];
        }
        $sql = '';
        foreach ($rows as $row) {
            $vals = [];
            foreach ((array) $row as $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } elseif (is_int($v) || is_float($v)) {
                    $vals[] = (string) $v;
                } else {
                    $vals[] = "'" . addslashes((string) $v) . "'";
                }
            }
            $sql .= 'INSERT INTO `' . $safe . '` VALUES (' . implode(',', $vals) . ");\n";
        }

        return ['sql' => $sql, 'count' => count($rows)];
    }

    private function execSqlDump(string $sql): void
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*\n/', $sql) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || str_starts_with($part, '----')) {
                continue;
            }
            // 禁止危险语句以外的多语句已按 ; 切开；拦截库级操作
            $upper = strtoupper($part);
            foreach (['DROP DATABASE', 'CREATE DATABASE', 'GRANT ', 'REVOKE '] as $bad) {
                if (str_contains($upper, $bad)) {
                    throw new BusinessException(ResultCode::FORBIDDEN, '备份中含有不允许的语句');
                }
            }
            try {
                Db::unprepared($part);
            } catch (Throwable $e) {
                throw new BusinessException(ResultCode::FAIL, '执行备份语句失败: ' . $e->getMessage());
            }
        }
    }

    private function sizeCount(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) max(0, $size);
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            ++$i;
        }

        return round($n, 2) . ' ' . $units[$i];
    }

    private function dirSize(string $dir): int
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/\\') . '/*') ?: [] as $file) {
            $size += is_file($file) ? (int) filesize($file) : 0;
        }

        return $size;
    }

    private function removeDir(string $dir): void
    {
        $files = scandir($dir) ?: [];
        foreach ($files as $base) {
            if ($base === '.' || $base === '..') {
                continue;
            }
            $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $base;
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($dir);
    }
}
