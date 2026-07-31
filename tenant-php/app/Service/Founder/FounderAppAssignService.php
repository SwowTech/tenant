<?php

declare(strict_types=1);

namespace App\Service\Founder;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\App\AppEdition;
use App\Library\App\AppLicense;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Model\OpsTenant;
use App\Service\App\AppInstallService;
use Hyperf\DbConnection\Db;
use Mine\AppStore\Plugin;
use Plugin\MineAdmin\AppStore\Service\Service as AppStoreService;
use Throwable;

/**
 * 创始人将本地/已下载应用分配到业务租户（写 cy_{id}_tenant_installed_app）.
 *
 * 不在构造注入 AppStore 插件 Service：生产环境常关 APP_DEBUG / 未装插件，硬依赖会导致
 * FounderTenantService（含 suggest-domain）整类 DI 失败。
 */
final class FounderAppAssignService
{
    private const IDENTIFIER_PATTERN = '/\A[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+\z/';

    private const STATUS_ENABLED = 1;

    private const STATUS_DISABLED = 2;

    public function __construct(
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly AppInstallService $appInstallService,
    ) {}

    /**
     * 把 apps/ 目录下带 app.json 的本地应用全部附加给租户（自助注册默认可用）.
     */
    public function assignAllLocalFilesystemApps(int $tenantId): int
    {
        $attached = 0;
        $root = AppPath::root();
        foreach (glob($root . '/*/*/app.json') ?: [] as $file) {
            if (! is_string($file)) {
                continue;
            }
            $dir = dirname($file);
            $app = basename($dir);
            $vendor = basename(dirname($dir));
            $identifier = $vendor . '/' . $app;
            try {
                $manifest = AppManifest::load($identifier);
                if (! AppEdition::shouldAutoAssign($manifest)) {
                    continue;
                }
                $version = (string) ($manifest['version'] ?? '1.0.0');
                $this->assign($tenantId, $identifier, $version !== '' ? $version : '1.0.0');
                ++$attached;
            } catch (Throwable) {
                continue;
            }
        }

        return $attached;
    }

    /**
     * @return array<string, array{status: mixed, version: mixed, description: mixed, author: mixed}>
     */
    public function listAssignableApps(): array
    {
        $list = $this->listLocalPlugins();
        $root = AppPath::root();
        foreach (glob($root . '/*/*/app.json') ?: [] as $file) {
            if (! is_string($file)) {
                continue;
            }
            $dir = dirname($file);
            $app = basename($dir);
            $vendor = basename(dirname($dir));
            $identifier = $vendor . '/' . $app;
            try {
                $manifest = AppManifest::load($identifier);
            } catch (Throwable) {
                continue;
            }
            $list[$identifier] = [
                'status' => true,
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'description' => (string) ($manifest['title'] ?? $identifier),
                'author' => $vendor,
            ];
        }

        return $list;
    }

    /**
     * @return list<array{identifier: string, version: string, edition: string, family: string, status: int, installed_at: ?string, expires_at: ?string, expired: bool, expires_label: string}>
     */
    public function listTenantApps(int $tenantId): array
    {
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppSchema();
            $rows = Db::table('tenant_installed_app')
                ->orderBy('id')
                ->get(['identifier', 'version', 'edition', 'family', 'status', 'installed_at', 'expires_at']);
            $out = [];
            foreach ($rows as $row) {
                $identifier = (string) $row->identifier;
                $expiresAt = isset($row->expires_at) && $row->expires_at !== null && $row->expires_at !== ''
                    ? (string) $row->expires_at
                    : null;
                $out[] = [
                    'identifier' => $identifier,
                    'version' => (string) $row->version,
                    'edition' => (string) ($row->edition ?? ''),
                    'family' => (string) (($row->family ?? '') !== '' ? $row->family : $identifier),
                    'status' => (int) $row->status,
                    'installed_at' => $row->installed_at !== null ? (string) $row->installed_at : null,
                    'expires_at' => $expiresAt,
                    'expired' => AppLicense::isExpired($expiresAt),
                    'expires_label' => AppLicense::formatLabel($expiresAt),
                ];
            }

            return $out;
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    /**
     * @param list<array{identifier?: mixed, version?: mixed}> $apps
     * @return array{attached: int, expires_at: ?string}
     */
    public function assignMany(int $tenantId, array $apps, int $years = 0, int $months = 0): array
    {
        if ($apps === []) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请至少选择一个应用');
        }
        $expiresAt = AppLicense::calcExpiresAt($years, $months);
        $normalized = [];
        foreach ($apps as $i => $app) {
            if (! is_array($app)) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, "apps[{$i}] 无效");
            }
            $id = $this->normalizeIdentifier(is_string($app['identifier'] ?? null) ? $app['identifier'] : '');
            $ver = trim(is_string($app['version'] ?? null) ? $app['version'] : '');
            if ($ver === '') {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, "apps[{$i}] 缺少 version");
            }
            $normalized[] = ['identifier' => $id, 'version' => $ver];
        }
        // 触发租户激活校验（与 assign 一致）
        $this->requireActiveTenantPrefix($tenantId);

        $attached = 0;
        try {
            foreach ($normalized as $item) {
                $this->assign($tenantId, $item['identifier'], $item['version'], $expiresAt);
                ++$attached;
            }
        } catch (BusinessException $e) {
            $base = (string) ($e->getResponse()->message ?? $e->getMessage());
            throw new BusinessException(
                ResultCode::UNPROCESSABLE_ENTITY,
                $base . "（已成功附加 {$attached} 个）",
            );
        }

        return ['attached' => $attached, 'expires_at' => $expiresAt];
    }

    public function assign(int $tenantId, string $identifier, string $version, ?string $expiresAt = null): void
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $version = trim($version);
        if ($version === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '缺少 version');
        }

        $prefix = $this->requireActiveTenantPrefix($tenantId);

        if (is_file(AppPath::appDir($identifier) . '/app.json')) {
            $this->appInstallService->enableForTenant($tenantId, $identifier, $version, $expiresAt);

            return;
        }

        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppSchema();
            $pluginPath = Plugin::PLUGIN_PATH . '/' . $identifier;
            if (! is_dir($pluginPath)) {
                $this->requireAppStore()->download([
                    'identifier' => $identifier,
                    'version' => $version,
                ]);
            }

            $existing = Db::table('tenant_installed_app')
                ->where('identifier', $identifier)
                ->first();

            if ($existing !== null) {
                $this->upsertInstalledApp($identifier, $version, $expiresAt);
                return;
            }

            $lockFile = $pluginPath . '/' . Plugin::INSTALL_LOCK_FILE;
            if (is_file($lockFile)) {
                // 全局已装：只写入/启用租户安装记录
                $this->upsertInstalledApp($identifier, $version, $expiresAt);
                return;
            }

            $this->requireAppStore()->install([
                'identifier' => $identifier,
                'version' => $version,
            ]);
            $this->upsertInstalledApp($identifier, $version, $expiresAt);
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    public function setAppStatus(int $tenantId, string $identifier, int $status): void
    {
        if (! in_array($status, [self::STATUS_ENABLED, self::STATUS_DISABLED], true)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'status 仅支持 1 或 2');
        }
        $identifier = $this->normalizeIdentifier($identifier);
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppSchema();
            $existing = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
            if ($existing === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '该租户未附加此应用');
            }
            Db::table('tenant_installed_app')->where('identifier', $identifier)->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    /**
     * 从租户移除已附加应用（删安装记录 + 解绑该应用域名；不删磁盘 apps/ 包）.
     */
    public function removeTenantApp(int $tenantId, string $identifier): void
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppSchema();
            $existing = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
            if ($existing === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '该租户未附加此应用');
            }
            Db::table('tenant_installed_app')->where('identifier', $identifier)->delete();
        } finally {
            $this->dynamicTablePrefix->reset();
        }

        try {
            $this->appInstallService->unbindDomain($tenantId, $identifier);
        } catch (Throwable) {
            // 无域名绑定或表不存在时忽略
        }
    }

    /**
     * 修改已附加应用：状态 / 授权有效期（年+月，0+0=永久）.
     *
     * @return array{identifier:string,status:int,expires_at:?string,expires_label:string,expired:bool}
     */
    public function updateTenantApp(int $tenantId, string $identifier, ?int $status, ?int $years, ?int $months): array
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppSchema();
            $existing = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
            if ($existing === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '该租户未附加此应用');
            }

            $patch = ['updated_at' => date('Y-m-d H:i:s')];
            if ($status !== null) {
                if (! in_array($status, [self::STATUS_ENABLED, self::STATUS_DISABLED], true)) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'status 仅支持 1 或 2');
                }
                $patch['status'] = $status;
            }
            if ($years !== null || $months !== null) {
                $patch['expires_at'] = AppLicense::calcExpiresAt((int) ($years ?? 0), (int) ($months ?? 0));
            }
            if (count($patch) === 1) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请指定要修改的状态或有效期');
            }

            Db::table('tenant_installed_app')->where('identifier', $identifier)->update($patch);
            $row = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
            $expiresAt = isset($row->expires_at) && $row->expires_at !== null && $row->expires_at !== ''
                ? (string) $row->expires_at
                : null;

            return [
                'identifier' => $identifier,
                'status' => (int) $row->status,
                'expires_at' => $expiresAt,
                'expired' => AppLicense::isExpired($expiresAt),
                'expires_label' => AppLicense::formatLabel($expiresAt),
            ];
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if (! preg_match(self::IDENTIFIER_PATTERN, $identifier)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '非法 identifier，需符合 vendor/name');
        }

        return $identifier;
    }

    /**
     * 本地 plugin/ 目录列表（不依赖 AppStore 插件 Service）.
     *
     * @return array<string, array{status: mixed, version: mixed, description: mixed, author: mixed}>
     */
    private function listLocalPlugins(): array
    {
        $items = [];
        try {
            foreach (Plugin::getPluginJsonPaths() as $splFileInfo) {
                $info = Plugin::read($splFileInfo->getRelativePath());
                if ($info === []) {
                    continue;
                }
                $name = (string) ($info['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $items[$name] = [
                    'status' => $info['status'] ?? false,
                    'version' => $info['version'] ?? '1.0.0',
                    'description' => $info['description'] ?? $name,
                    'author' => $info['author'] ?? '',
                ];
            }
        } catch (Throwable) {
            return $items;
        }

        return $items;
    }

    private function requireAppStore(): AppStoreService
    {
        if (! class_exists(AppStoreService::class)) {
            throw new BusinessException(
                ResultCode::FAIL,
                '应用商店插件未安装或未启用，无法从商店下载/安装插件。请先安装 mine-admin/app-store，或仅分配 apps/ 本地应用',
            );
        }

        try {
            return make(AppStoreService::class);
        } catch (Throwable $e) {
            throw new BusinessException(
                ResultCode::FAIL,
                '应用商店服务不可用：' . $e->getMessage(),
            );
        }
    }

    /** @return string table prefix */
    private function requireActiveTenantPrefix(int $tenantId): string
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId) {
            $tenant = OpsTenant::query()->find($tenantId);
            if (! $tenant) {
                throw new BusinessException(ResultCode::NOT_FOUND, '租户不存在');
            }
            if ((int) $tenant->status !== FounderTenantService::STATUS_ACTIVE) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '租户未激活，无法分配应用');
            }

            return $tenant->table_prefix !== '' && $tenant->table_prefix !== null
                ? (string) $tenant->table_prefix
                : TenantInfo::tablePrefixForId($tenantId);
        });
    }

    private function upsertInstalledApp(string $identifier, string $version, ?string $expiresAt = null): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = Db::table('tenant_installed_app')
            ->where('identifier', $identifier)
            ->first();
        if ($existing !== null) {
            $patch = [
                'version' => $version,
                'status' => self::STATUS_ENABLED,
                'expires_at' => $expiresAt,
                'updated_at' => $now,
            ];
            AppEdition::fillEditionFields($patch, $identifier);
            Db::table('tenant_installed_app')
                ->where('identifier', $identifier)
                ->update($patch);

            return;
        }

        $row = [
            'identifier' => $identifier,
            'version' => $version,
            'status' => self::STATUS_ENABLED,
            'config' => null,
            'installed_at' => $now,
            'expires_at' => $expiresAt,
            'updated_at' => $now,
        ];
        AppEdition::fillEditionFields($row, $identifier);
        Db::table('tenant_installed_app')->insert($row);
    }

    private function ensureInstalledAppSchema(): void
    {
        if (! \Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
            return;
        }
        if (! \Hyperf\Database\Schema\Schema::hasColumn('tenant_installed_app', 'expires_at')) {
            \Hyperf\Database\Schema\Schema::table('tenant_installed_app', function (\Hyperf\Database\Schema\Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->after('installed_at');
            });
        }
        if (! \Hyperf\Database\Schema\Schema::hasColumn('tenant_installed_app', 'edition')) {
            \Hyperf\Database\Schema\Schema::table('tenant_installed_app', function (\Hyperf\Database\Schema\Blueprint $table) {
                $table->string('edition', 32)->default('')->after('version');
            });
        }
        if (! \Hyperf\Database\Schema\Schema::hasColumn('tenant_installed_app', 'family')) {
            \Hyperf\Database\Schema\Schema::table('tenant_installed_app', function (\Hyperf\Database\Schema\Blueprint $table) {
                $table->string('family', 100)->default('')->after('edition');
                $table->index('family');
            });
        }
    }
}
