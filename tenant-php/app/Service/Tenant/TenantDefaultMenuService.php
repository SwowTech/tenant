<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Library\Tenant\DynamicTablePrefix;
use Hyperf\DbConnection\Db;

/**
 * 租户默认菜单（开通 / 回填）.
 */
final class TenantDefaultMenuService
{
    public const MY_APPS_NAME = 'apps:mine';

    public const SETTING_ROOT_NAME = 'setting';

    public const APP_DOMAINS_NAME = 'setting:app-domains';

    public function __construct(
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    /**
     * 确保当前前缀上下文中有「我的应用」主菜单，并绑定 SuperAdmin.
     */
    public function ensureMyAppsMenu(): int
    {
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

        $existing = Db::table('menu')->where('name', self::MY_APPS_NAME)->value('id');
        if ($existing) {
            $menuId = (int) $existing;
            Db::table('menu')->where('id', $menuId)->update([
                'path' => '/apps/mine',
                'component' => 'base/views/apps/mine/index',
                'redirect' => '',
                'status' => 1,
                'meta' => $meta,
                'updated_at' => $now,
            ]);
        } else {
            $menuId = (int) Db::table('menu')->insertGetId([
                'parent_id' => 0,
                'name' => self::MY_APPS_NAME,
                'path' => '/apps/mine',
                'component' => 'base/views/apps/mine/index',
                'redirect' => '',
                'status' => 1,
                'sort' => 5,
                'created_by' => 0,
                'updated_by' => 0,
                'remark' => 'tenant-my-apps',
                'meta' => $meta,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->bindSuperAdmin($menuId);

        return $menuId;
    }

    /**
     * 移除「站点设置 → 应用域名」菜单（域名改由「我的应用」设置弹框管理）.
     */
    public function removeAppDomainsMenu(): void
    {
        $pageId = (int) (Db::table('menu')->where('name', self::APP_DOMAINS_NAME)->value('id') ?: 0);
        if ($pageId > 0) {
            Db::table('role_belongs_menu')->where('menu_id', $pageId)->delete();
            Db::table('menu')->where('id', $pageId)->delete();
        }

        $rootId = (int) (Db::table('menu')->where('name', self::SETTING_ROOT_NAME)->value('id') ?: 0);
        if ($rootId > 0) {
            $redirect = (string) (Db::table('menu')->where('id', $rootId)->value('redirect') ?: '');
            if ($redirect === '/setting/app-domains') {
                $sitePath = (string) (Db::table('menu')->where('name', 'setting:site')->value('path') ?: '');
                Db::table('menu')->where('id', $rootId)->update([
                    'redirect' => $sitePath !== '' ? $sitePath : '/setting/site',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function ensureDefaultMenus(): void
    {
        $this->ensureMyAppsMenu();
        $this->removeAppDomainsMenu();
    }

    public function ensureMyAppsMenuForTenant(int $tenantId): int
    {
        $prefix = 'cy_' . $tenantId . '_';
        $this->dynamicTablePrefix->apply($prefix);
        try {
            if (! \Hyperf\Database\Schema\Schema::hasTable('menu')) {
                return 0;
            }

            return $this->ensureMyAppsMenu();
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    public function ensureDefaultMenusForTenant(int $tenantId): void
    {
        $prefix = 'cy_' . $tenantId . '_';
        $this->dynamicTablePrefix->apply($prefix);
        try {
            if (! \Hyperf\Database\Schema\Schema::hasTable('menu')) {
                return;
            }
            $this->ensureDefaultMenus();
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    private function bindSuperAdmin(int $menuId): void
    {
        if ($menuId <= 0) {
            return;
        }
        $roleId = (int) Db::table('role')->where('code', 'SuperAdmin')->value('id');
        if ($roleId <= 0) {
            return;
        }
        $bound = Db::table('role_belongs_menu')
            ->where('role_id', $roleId)
            ->where('menu_id', $menuId)
            ->exists();
        if (! $bound) {
            Db::table('role_belongs_menu')->insert([
                'role_id' => $roleId,
                'menu_id' => $menuId,
            ]);
        }
    }
}
