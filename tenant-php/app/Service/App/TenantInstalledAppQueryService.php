<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Library\App\AppEdition;
use App\Library\App\AppLicense;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Support\AppUrl;
use App\Library\Tenant\TenantContext;
use Hyperf\DbConnection\Db;
use Throwable;

/**
 * 当前租户已安装的应用（供「我的应用」，含停用）.
 */
final class TenantInstalledAppQueryService
{
    private const STATUS_ENABLED = 1;

    /**
     * 当前租户已安装应用（含停用；网关仍只放行启用中的）.
     *
     * @return list<array{identifier:string,title:string,version:string,edition:string,family:string,upgrades_from:list<string>,can_migrate_from:list<string>,status:int,enabled:bool,installed_at:?string,expires_at:?string,expired:bool,expires_label:string,open_url:string,has_web:bool}>
     */
    public function listEnabled(): array
    {
        $tenant = TenantContext::get();
        if ($tenant === null) {
            return [];
        }
        if (! \Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
            return [];
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

        $rows = Db::table('tenant_installed_app')
            ->orderBy('id')
            ->get(['identifier', 'version', 'edition', 'family', 'status', 'installed_at', 'expires_at']);

        $installedIds = [];
        foreach ($rows as $row) {
            $installedIds[(string) $row->identifier] = true;
        }

        $out = [];
        foreach ($rows as $row) {
            $identifier = (string) $row->identifier;
            $edition = (string) ($row->edition ?? '');
            $family = (string) ($row->family ?? '');
            if ($family === '') {
                $family = $identifier;
            }
            $upgradesFrom = [];
            $title = $identifier;
            $hasWeb = false;
            if (is_file(AppPath::appDir($identifier) . '/app.json')) {
                try {
                    $manifest = AppManifest::load($identifier);
                    $title = (string) ($manifest['title'] ?? $manifest['name'] ?? $identifier);
                    $hasWeb = true;
                    $upgradesFrom = AppEdition::upgradesFromFromManifest($manifest);
                } catch (Throwable) {
                    $hasWeb = is_dir(AppPath::appDir($identifier) . '/web');
                }
            } else {
                $pluginJson = BASE_PATH . '/plugin/' . $identifier . '/mine.json';
                if (is_file($pluginJson)) {
                    try {
                        $raw = json_decode((string) file_get_contents($pluginJson), true);
                        if (is_array($raw)) {
                            $title = (string) ($raw['description'] ?? $raw['name'] ?? $identifier);
                        }
                    } catch (Throwable) {
                        // keep identifier as title
                    }
                }
            }
            $status = (int) $row->status;
            $enabled = $status === self::STATUS_ENABLED;
            $expiresAt = isset($row->expires_at) && $row->expires_at !== null && $row->expires_at !== ''
                ? (string) $row->expires_at
                : null;
            $expired = AppLicense::isExpired($expiresAt);
            $canMigrateFrom = [];
            foreach ($upgradesFrom as $fromId) {
                if (isset($installedIds[$fromId])) {
                    $canMigrateFrom[] = $fromId;
                }
            }

            $out[] = [
                'identifier' => $identifier,
                'title' => $title,
                'version' => (string) $row->version,
                'edition' => $edition,
                'family' => $family,
                'upgrades_from' => $upgradesFrom,
                'can_migrate_from' => $canMigrateFrom,
                'status' => $status,
                'enabled' => $enabled,
                'installed_at' => $row->installed_at !== null ? (string) $row->installed_at : null,
                'expires_at' => $expiresAt,
                'expired' => $expired,
                'expires_label' => AppLicense::formatLabel($expiresAt),
                'open_url' => ($hasWeb && $enabled && ! $expired) ? AppUrl::appOpenUrl($identifier, $tenant->domain) : '',
                'has_web' => $hasWeb,
            ];
        }

        return $out;
    }

    /**
     * 按 family 分组已装应用（同 family 可并存多档）.
     *
     * @param list<array<string, mixed>>|null $items
     * @return list<array{family: string, editions: list<array<string, mixed>>}>
     */
    public function listGroupedByFamily(?array $items = null): array
    {
        $items ??= $this->listEnabled();
        $groups = [];
        foreach ($items as $item) {
            $family = (string) ($item['family'] ?: $item['identifier']);
            if (! isset($groups[$family])) {
                $groups[$family] = [
                    'family' => $family,
                    'editions' => [],
                ];
            }
            $groups[$family]['editions'][] = $item;
        }

        return array_values($groups);
    }
}
