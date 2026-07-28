<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Library\Support\InProcessMigrate;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Model\Permission\Role;
use App\Model\Permission\User;
use App\Service\Tenant\TenantDefaultMenuService;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

final class TenantProvisionService
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly TenantDefaultMenuService $defaultMenus,
    ) {}

    public function provision(int $id, string $adminUser, string $adminPass): void
    {
        $prefix = TenantInfo::tablePrefixForId($id);
        $this->dynamicTablePrefix->apply($prefix);

        try {
            // 重试开通时清掉上次半成品，避免 duplicate table/index
            $this->wipePrefixedTables($prefix);
            InProcessMigrate::run($this->container);

            Db::transaction(function () use ($adminUser, $adminPass): void {
                if (User::query()->where('username', $adminUser)->exists()) {
                    return;
                }
                $user = User::create([
                    'username' => $adminUser,
                    'password' => $adminPass,
                    'user_type' => '100',
                    'nickname' => '租户管理员',
                    'status' => 1,
                    'created_by' => 0,
                    'updated_by' => 0,
                ]);
                $role = Role::query()->firstOrCreate(
                    ['code' => 'SuperAdmin'],
                    ['name' => '超级管理员', 'status' => 1]
                );
                $user->roles()->sync([$role->id]);
            });

            $this->defaultMenus->ensureDefaultMenus();
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    /**
     * Drop all tables for this tenant prefix (full names, no double-prefix).
     */
    private function wipePrefixedTables(string $prefix): void
    {
        if ($prefix === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            return;
        }

        // SHOW TABLES 不支持预处理占位符；LIKE 中 _/% 需转义（前缀形如 cy_31_）
        $like = addcslashes($prefix, "\\%_'") . '%';
        $rows = Db::select("SHOW TABLES LIKE '{$like}'");
        if ($rows === []) {
            return;
        }

        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($rows as $row) {
                $name = (string) array_values((array) $row)[0];
                if ($name === '' || ! str_starts_with($name, $prefix)) {
                    continue;
                }
                Db::statement('DROP TABLE IF EXISTS `' . str_replace('`', '``', $name) . '`');
            }
        } finally {
            Db::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
