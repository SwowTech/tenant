<?php

declare(strict_types=1);

use App\Model\Permission\Menu;
use App\Model\Permission\Role;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

/**
 * 创始人「租户管理」菜单（仅菜单/权限，不写入任何租户或附加应用业务数据）.
 *
 * 文件名以 z_ 开头：必须在 menu_seeder（会 truncate menu）和 user_seeder（会 truncate role）之后执行，
 * 否则新装库会缺「租户管理」菜单与 founder 角色绑定。
 */
class ZFounderMenu20260728 extends Seeder
{
    public function run(): void
    {
        if (Menu::query()->where('name', 'founder')->exists()) {
            $this->ensureChildren();
            $this->ensureFounderRole();
            $this->bindRoles();

            return;
        }

        $root = Menu::create([
            'name' => 'founder',
            'path' => '/founder',
            'parent_id' => 0,
            'component' => '',
            'redirect' => '/founder/tenants',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'sort' => 0,
            'meta' => [
                'title' => '租户管理',
                'i18n' => 'menu.founder',
                'icon' => 'ri:user-star-line',
                'type' => 'M',
                'hidden' => 0,
                'componentPath' => 'modules/',
                'componentSuffix' => '.vue',
                'breadcrumbEnable' => 1,
                'copyright' => 1,
                'cache' => 1,
                'affix' => 0,
            ],
        ]);

        $this->ensureTenantsPage($root->id);
        $this->ensureAppDomains($root->id);
        $this->ensureFounderRole();
        $this->bindRoles();
    }

    private function ensureChildren(): void
    {
        $founderId = (int) Menu::query()->where('name', 'founder')->value('id');
        if ($founderId <= 0) {
            return;
        }
        $this->ensureTenantsPage($founderId);
        $this->ensureAppDomains($founderId);
    }

    private function ensureTenantsPage(int $founderId): void
    {
        $tenants = Menu::query()->where('name', 'founder:tenants')->first();
        if (! $tenants) {
            $tenants = Menu::create([
                'name' => 'founder:tenants',
                'path' => '/founder/tenants',
                'parent_id' => $founderId,
                'component' => 'base/views/founder/tenants/index',
                'redirect' => '',
                'created_by' => 0,
                'updated_by' => 0,
                'remark' => '',
                'sort' => 10,
                'meta' => [
                    'title' => '租户列表',
                    'i18n' => 'menu.founder:tenants',
                    'icon' => 'ri:building-line',
                    'type' => 'M',
                    'hidden' => 0,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ],
            ]);
        }

        foreach ([
            'founder:tenants:list' => '查看租户',
            'founder:tenants:create' => '创建租户',
            'founder:tenants:update' => '更新租户',
            'founder:tenants:provision' => '重试开通',
            'founder:tenants:assign-app' => '分配应用',
            'founder:apps:list' => '可分配应用列表',
        ] as $name => $title) {
            $this->createButton((int) $tenants->id, $name, $title);
        }
    }

    private function ensureAppDomains(int $founderId): void
    {
        $page = Menu::query()->where('name', 'founder:app-domains')->first();
        if (! $page) {
            $page = Menu::create([
                'name' => 'founder:app-domains',
                'path' => '/founder/app-domains',
                'parent_id' => $founderId,
                'component' => 'base/views/founder/app-domains/index',
                'redirect' => '',
                'created_by' => 0,
                'updated_by' => 0,
                'remark' => '',
                'sort' => 20,
                'meta' => [
                    'title' => '应用域名',
                    'i18n' => 'menu.founder:app-domains',
                    'icon' => 'ri:links-line',
                    'type' => 'M',
                    'hidden' => 0,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ],
            ]);
        }

        foreach ([
            'founder:app-domains:list' => '查看应用域名',
            'founder:app-domains:create' => '绑定应用域名',
            'founder:app-domains:update' => '更新应用域名',
            'founder:app-domains:delete' => '解绑应用域名',
        ] as $name => $title) {
            $this->createButton((int) $page->id, $name, $title);
        }
    }

    private function createButton(int $parentId, string $name, string $title): void
    {
        if (Menu::query()->where('name', $name)->exists()) {
            return;
        }
        Menu::create([
            'name' => $name,
            'path' => '',
            'parent_id' => $parentId,
            'component' => '',
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'sort' => 0,
            'meta' => [
                'title' => $title,
                'type' => 'B',
                'hidden' => 1,
                'cache' => 1,
                'affix' => 0,
            ],
        ]);
    }

    private function ensureFounderRole(): void
    {
        $role = Role::query()->firstOrCreate(
            ['code' => 'founder'],
            [
                'name' => '创始人',
                'status' => 1,
                'sort' => 0,
                'created_by' => 0,
                'updated_by' => 0,
                'remark' => '主创始人角色',
            ]
        );
        $bound = Db::table('user_belongs_role')
            ->where('user_id', 1)
            ->where('role_id', $role->id)
            ->exists();
        if (! $bound && Db::table('user')->where('id', 1)->exists()) {
            Db::table('user_belongs_role')->insert([
                'user_id' => 1,
                'role_id' => $role->id,
            ]);
        }
    }

    private function bindRoles(): void
    {
        $ids = Menu::query()
            ->where('name', 'founder')
            ->orWhere('name', 'like', 'founder:%')
            ->pluck('id')
            ->all();
        if ($ids === []) {
            return;
        }
        $roleIds = Role::query()
            ->whereIn('code', ['founder', 'SuperAdmin'])
            ->pluck('id')
            ->all();
        foreach ($roleIds as $roleId) {
            foreach ($ids as $menuId) {
                $exists = Db::table('role_belongs_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->exists();
                if (! $exists) {
                    Db::table('role_belongs_menu')->insert([
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                    ]);
                }
            }
        }
    }
}
