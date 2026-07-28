<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\App\AppDomainBinding;
use App\Library\App\AppEdition;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\App\AppProcessManager;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Model\OpsTenant;
use App\Service\Founder\FounderTenantService;
use Hyperf\DbConnection\Db;
use RuntimeException;
use ZipArchive;

final class AppInstallService
{
    public function __construct(
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly AppProcessManager $processManager,
        private readonly AppDomainBinding $appDomainBinding,
    ) {}

    public function enableForTenant(int $tenantId, string $identifier, string $version, ?string $expiresAt = null): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $version = trim($version);
        if ($version === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '缺少 version');
        }
        if (! is_file(AppPath::appDir($identifier) . '/app.json')) {
            throw new BusinessException(ResultCode::NOT_FOUND, 'apps 目录中不存在该应用');
        }
        AppManifest::load($identifier);
        // 安装时分配固定进程端口（29100 起顺序）
        $this->processManager->assignPort($identifier);
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppTable();
            $this->upsertInstalledApp($identifier, $version, $expiresAt);
        } finally {
            $this->dynamicTablePrefix->reset();
        }
        if ($identifier === XunlianTraceTp6DbProvisioner::IDENTIFIER) {
            (new XunlianTraceTp6DbProvisioner())->provisionForTenant($tenantId, $prefix);
        }
        $this->processManager->ensureRunning($identifier);
    }

    /**
     * 为租户下的某个应用新增一条独立域名（可绑多条）.
     *
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}
     */
    public function addDomain(
        int $tenantId,
        string $identifier,
        string $domain,
        string $scheme = 'https',
        bool $asPrimary = false,
    ): array {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->requireActiveTenantPrefix($tenantId);
        if (! is_file(AppPath::appDir($identifier) . '/app.json')) {
            throw new BusinessException(ResultCode::NOT_FOUND, 'apps 目录中不存在该应用');
        }
        $this->assertAppInstalled($tenantId, $identifier);

        return $this->appDomainBinding->add($tenantId, $identifier, $domain, $scheme, $asPrimary);
    }

    /**
     * 兼容旧脚本：无则新增，有则更新主域名.
     */
    public function bindDomain(int $tenantId, string $identifier, string $domain, string $scheme = 'https'): string
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->requireActiveTenantPrefix($tenantId);
        if (! is_file(AppPath::appDir($identifier) . '/app.json')) {
            throw new BusinessException(ResultCode::NOT_FOUND, 'apps 目录中不存在该应用');
        }
        $this->assertAppInstalled($tenantId, $identifier);
        $this->appDomainBinding->bind($tenantId, $identifier, $domain, $scheme);

        return (string) $this->appDomainBinding->publicBase($tenantId, $identifier);
    }

    /**
     * @param array{domain?: string, scheme?: string, is_primary?: bool} $data
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}
     */
    public function updateDomain(int $id, int $tenantId, array $data): array
    {
        $this->requireActiveTenantPrefix($tenantId);

        return $this->appDomainBinding->update($id, $tenantId, $data);
    }

    public function removeDomain(int $id, int $tenantId): void
    {
        $this->requireActiveTenantPrefix($tenantId);
        $this->appDomainBinding->removeById($id, $tenantId);
    }

    public function unbindDomain(int $tenantId, string $identifier): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->appDomainBinding->unbind($tenantId, $identifier);
    }

    /**
     * @return list<array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}>
     */
    public function listDomains(?int $tenantId = null, ?string $identifier = null): array
    {
        return $this->appDomainBinding->list($tenantId, $identifier);
    }

    public function getBoundDomain(int $tenantId, string $identifier): ?string
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);

        return $this->appDomainBinding->publicBase($tenantId, $identifier);
    }

    /**
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}|null
     */
    public function findDomain(int $id): ?array
    {
        return $this->appDomainBinding->findById($id);
    }

    private function assertAppInstalled(int $tenantId, string $identifier): void
    {
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $this->ensureInstalledAppTable();
            $exists = Db::table('tenant_installed_app')->where('identifier', $identifier)->exists();
            if (! $exists) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该应用未安装到当前租户');
            }
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    private function ensureInstalledAppTable(): void
    {
        if (! \Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
            \Hyperf\Database\Schema\Schema::create('tenant_installed_app', function (\Hyperf\Database\Schema\Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('identifier', 100);
                $table->string('version', 20);
                $table->string('edition', 32)->default('');
                $table->string('family', 100)->default('');
                $table->tinyInteger('status')->default(1);
                $table->json('config')->nullable();
                $table->dateTime('installed_at');
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('updated_at');
                $table->unique('identifier');
                $table->index('family');
            });

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

    public function disableForTenant(int $tenantId, string $identifier): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $prefix = $this->requireActiveTenantPrefix($tenantId);
        $this->dynamicTablePrefix->apply($prefix);
        try {
            Db::table('tenant_installed_app')->where('identifier', $identifier)->update([
                'status' => 2,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    /**
     * 解压 zip 到 apps/{vendor}/{app}/（防路径穿越；name 必须匹配）.
     *
     * @return string identifier
     */
    public function installFromZip(string $zipPath, ?string $expectedIdentifier = null): string
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('应用包不存在');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('无法打开应用包');
        }

        try {
            $manifestEntry = $this->findManifestEntry($zip);
            $raw = $zip->getFromName($manifestEntry);
            if ($raw === false) {
                throw new RuntimeException('无法读取 app.json');
            }
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true);
            if (! is_array($decoded) || ! is_string($decoded['name'] ?? null)) {
                throw new RuntimeException('app.json 无效');
            }
            $identifier = AppPath::assertSafeIdentifier((string) $decoded['name']);
            if ($expectedIdentifier !== null && $expectedIdentifier !== '' && $expectedIdentifier !== $identifier) {
                throw new RuntimeException("包内 name={$identifier} 与期望 {$expectedIdentifier} 不一致");
            }

            $prefix = $this->zipRootPrefix($manifestEntry);
            $target = AppPath::appDir($identifier);
            if (is_dir($target) && is_file($target . '/app.json')) {
                $existing = AppManifest::load($identifier);
                if (($existing['version'] ?? '') === ($decoded['version'] ?? '') && is_dir($target . '/web')) {
                    $this->processManager->assignPort($identifier);

                    return $identifier;
                }
            }

            $tmp = dirname($target) . '/.tmp-' . bin2hex(random_bytes(4));
            if (is_dir($tmp)) {
                $this->rrmdir($tmp);
            }
            mkdir($tmp, 0755, true);

            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = $zip->getNameIndex($i);
                if (! is_string($name) || $name === '' || str_ends_with($name, '/')) {
                    continue;
                }
                $rel = $prefix === '' ? $name : (str_starts_with($name, $prefix) ? substr($name, strlen($prefix)) : null);
                if ($rel === null || $rel === '' || str_contains($rel, '..')) {
                    continue;
                }
                $dest = $tmp . '/' . str_replace('\\', '/', $rel);
                $destDir = dirname($dest);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    throw new RuntimeException('解压失败: ' . $name);
                }
                if (file_put_contents($dest, $content) === false) {
                    throw new RuntimeException('写入失败: ' . $rel);
                }
            }

            if (! is_file($tmp . '/app.json')) {
                $this->rrmdir($tmp);
                throw new RuntimeException('解压后缺少 app.json');
            }

            $parent = dirname($target);
            if (! is_dir($parent)) {
                mkdir($parent, 0755, true);
            }
            if (is_dir($target)) {
                $this->rrmdir($target);
            }
            if (! rename($tmp, $target)) {
                $this->rrmdir($tmp);
                throw new RuntimeException('无法安装到 apps 目录');
            }

            AppManifest::load($identifier);
            $this->processManager->assignPort($identifier);

            return $identifier;
        } finally {
            $zip->close();
        }
    }

    /**
     * zip 是否为独立应用包（含 app.json）.
     */
    public function zipLooksLikeApp(string $zipPath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }
        try {
            return $this->findManifestEntry($zip) !== null;
        } catch (Throwable) {
            return false;
        } finally {
            $zip->close();
        }
    }

    private function findManifestEntry(ZipArchive $zip): string
    {
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name)) {
                continue;
            }
            $norm = str_replace('\\', '/', $name);
            if ($norm === 'app.json' || preg_match('#\A[^/]+/app\.json\z#', $norm) === 1) {
                return $name;
            }
        }
        throw new RuntimeException('zip 中未找到 app.json');
    }

    private function zipRootPrefix(string $manifestEntry): string
    {
        $norm = str_replace('\\', '/', $manifestEntry);
        if ($norm === 'app.json') {
            return '';
        }
        $pos = strrpos($norm, '/');
        if ($pos === false) {
            return '';
        }

        return substr($norm, 0, $pos + 1);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
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
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '租户未激活');
            }

            return $tenant->table_prefix !== '' && $tenant->table_prefix !== null
                ? (string) $tenant->table_prefix
                : TenantInfo::tablePrefixForId($tenantId);
        });
    }

    private function upsertInstalledApp(string $identifier, string $version, ?string $expiresAt = null): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = Db::table('tenant_installed_app')->where('identifier', $identifier)->first();
        if ($existing !== null) {
            $patch = [
                'version' => $version,
                'status' => 1,
                'expires_at' => $expiresAt,
                'updated_at' => $now,
            ];
            AppEdition::fillEditionFields($patch, $identifier);
            Db::table('tenant_installed_app')->where('identifier', $identifier)->update($patch);

            return;
        }
        $row = [
            'identifier' => $identifier,
            'version' => $version,
            'status' => 1,
            'config' => null,
            'installed_at' => $now,
            'expires_at' => $expiresAt,
            'updated_at' => $now,
        ];
        AppEdition::fillEditionFields($row, $identifier);
        Db::table('tenant_installed_app')->insert($row);
    }
}
