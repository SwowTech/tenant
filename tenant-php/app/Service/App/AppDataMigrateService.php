<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Tenant\TenantContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Throwable;

/**
 * 同租户库、跨 identifier 档位包的数据迁移（执行目标包 manifest 中的 SQL）.
 */
final class AppDataMigrateService
{
    public function __construct(
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function migrate(string $fromId, string $toId): void
    {
        $fromId = AppPath::assertSafeIdentifier($fromId);
        $toId = AppPath::assertSafeIdentifier($toId);
        if ($fromId === $toId) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '源与目标应用不能相同');
        }
        if (TenantContext::get() === null) {
            throw new BusinessException(ResultCode::FORBIDDEN, '缺少租户上下文');
        }

        $this->assertInstalled($fromId);
        $this->assertInstalled($toId);

        $meta = AppManifest::editionMeta($toId);
        if (! in_array($fromId, $meta['upgrades_from'], true)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '目标应用未声明可从该源迁移');
        }

        $relative = $meta['migrate'];
        if (! is_string($relative) || trim($relative) === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '目标应用未配置迁移脚本');
        }

        $sqlFile = self::resolveMigrateSqlPath(AppPath::appDir($toId), trim($relative));
        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '迁移脚本为空或无法读取');
        }

        $this->execSql($sql);

        $this->logger->info(sprintf(
            'App data migrate success: tenant=%s from=%s to=%s file=%s',
            (string) (TenantContext::id() ?? ''),
            $fromId,
            $toId,
            $sqlFile,
        ));
    }

    /**
     * 解析并校验迁移 SQL 相对路径（须在应用目录内且为 .sql）.
     */
    public static function resolveMigrateSqlPath(string $appDir, string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '非法迁移脚本路径');
        }

        $realApp = realpath($appDir);
        if ($realApp === false) {
            throw new BusinessException(ResultCode::NOT_FOUND, '目标应用目录不存在');
        }

        $path = $realApp . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $realFile = realpath($path);
        if ($realFile === false
            || ! str_starts_with(str_replace('\\', '/', $realFile), str_replace('\\', '/', $realApp) . '/')
            || ! str_ends_with(strtolower($realFile), '.sql')) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '迁移脚本不存在或不允许');
        }

        return $realFile;
    }

    private function assertInstalled(string $identifier): void
    {
        if (! \Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
            throw new BusinessException(ResultCode::NOT_FOUND, '应用未安装: ' . $identifier);
        }

        $row = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
        if ($row === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '应用未安装: ' . $identifier);
        }
    }

    private function execSql(string $sql): void
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*\n/', $sql) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            try {
                Db::unprepared($part);
            } catch (Throwable $e) {
                throw new BusinessException(ResultCode::FAIL, '执行迁移 SQL 失败: ' . $e->getMessage());
            }
        }
    }
}
