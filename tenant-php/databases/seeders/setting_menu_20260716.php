<?php

declare(strict_types=1);

use App\Model\Permission\Menu;
use Hyperf\Database\Seeders\Seeder;

/**
 * 站点设置菜单（初始安装）：
 * 云服务（升级/注册/诊断）→ 设置 → 常用工具 → 后台任务。
 * 不含「应用管理 / 附加应用 / 租户列表」等运营数据入口。
 */
class SettingMenu20260716 extends Seeder
{
    /** @var array<string, string> */
    private const API_BUTTONS = [
        'setting:site' => '站点设置',
        'setting:attachment' => '附件设置',
        'setting:systeminfo' => '系统信息',
        'setting:user-login' => '用户登录/注册设置',
    ];

    public function run(): void
    {
        if (Menu::query()->where('name', 'setting')->exists()) {
            $this->ensureCloudPages();

            return;
        }

        $this->seedTree();
    }

    public function seedTree(): void
    {
        $root = Menu::create([
            'name' => 'setting',
            'path' => '/setting',
            'parent_id' => 0,
            'component' => '',
            'redirect' => '/setting/cloud/upgrade',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'sort' => 90,
            'meta' => $this->metaM('站点设置', 'ri:settings-3-line', 'setting', true, 1),
        ]);

        // 1. 云服务
        $cloud = $this->createGroup($root->id, 'setting:cloud', '云服务', 'ri:cloud-line', 10);
        $this->createPage($cloud->id, 'setting:cloud:upgrade', '/setting/cloud/upgrade', 'base/views/setting/cloud/upgrade/index', '系统升级', 'ri:refresh-line', 10, 0);
        $this->createPage($cloud->id, 'setting:cloud:register', '/setting/cloud/register', 'base/views/setting/cloud/register/index', '注册站点', 'ri:registered-line', 20, 0);
        $this->createPage($cloud->id, 'setting:cloud-diagnose', '/setting/cloud-diagnose', 'base/views/setting/cloud/diagnose/index', '云服务诊断', 'ri:cloud-line', 30, 0);

        // 2. 设置
        $setting = $this->createGroup($root->id, 'setting:group', '设置', 'ri:settings-4-line', 20);
        $sort = 10;
        $site = $this->createPage($setting->id, 'setting:site', '/setting/site', 'base/views/setting/site/index', '站点设置', 'ri:global-line', $sort);
        $this->createButton($site->id, 'setting:site', self::API_BUTTONS['setting:site']);
        $sort += 10;

        $attachment = $this->createPage($setting->id, 'setting:attachment', '/setting/attachment', 'base/views/setting/attachment/index', '附件设置', 'ri:attachment-2', $sort);
        $this->createButton($attachment->id, 'setting:attachment', self::API_BUTTONS['setting:attachment']);
        $sort += 10;

        $systeminfo = $this->createPage($setting->id, 'setting:systeminfo', '/setting/systeminfo', 'base/views/setting/systeminfo/index', '系统信息', 'ri:information-line', $sort);
        $this->createButton($systeminfo->id, 'setting:systeminfo', self::API_BUTTONS['setting:systeminfo']);
        $sort += 10;

        $userLogin = $this->createPage($setting->id, 'setting:user-login', '/setting/user-login', 'base/views/setting/user-login/index', '用户登录/注册设置', 'ri:user-settings-line', $sort);
        $this->createButton($userLogin->id, 'setting:user-login', self::API_BUTTONS['setting:user-login']);

        // 3. 常用工具
        $tools = $this->createGroup($root->id, 'setting:tools', '常用工具', 'ri:tools-line', 30);
        $toolSort = 10;
        $this->createPage($tools->id, 'setting:tools:database', '/setting/tools/database', 'base/views/setting/tools/database/index', '数据库', 'ri:database-2-line', $toolSort);
        $toolSort += 10;
        $this->createPage($tools->id, 'setting:system-check', '/system/check', 'base/views/system/check/index', '系统常规检测', 'ri:health-book-line', $toolSort);

        // 「后台任务」不在初始 seed 创建：无定时任务子菜单时点击会 404。
        // 由 hyperf-crontab 插件安装脚本在挂菜单时创建 setting:job 分组。
    }

    /**
     * 已安装库：把仍指向 placeholder 的云服务页改到真实组件（不补插应用管理）.
     */
    private function ensureCloudPages(): void
    {
        $map = [
            'setting:cloud:upgrade' => ['path' => '/setting/cloud/upgrade', 'component' => 'base/views/setting/cloud/upgrade/index', 'title' => '系统升级', 'icon' => 'ri:refresh-line', 'sort' => 10],
            'setting:cloud:register' => ['path' => '/setting/cloud/register', 'component' => 'base/views/setting/cloud/register/index', 'title' => '注册站点', 'icon' => 'ri:registered-line', 'sort' => 20],
            'setting:cloud-diagnose' => ['path' => '/setting/cloud-diagnose', 'component' => 'base/views/setting/cloud/diagnose/index', 'title' => '云服务诊断', 'icon' => 'ri:cloud-line', 'sort' => 30],
        ];

        $cloudId = (int) Menu::query()->where('name', 'setting:cloud')->value('id');

        foreach ($map as $name => $cfg) {
            $row = Menu::query()->where('name', $name)->first();
            if ($row) {
                if ((string) $row->component !== $cfg['component']) {
                    $row->component = $cfg['component'];
                    $row->path = $cfg['path'];
                    $row->save();
                }
                continue;
            }
            if ($cloudId <= 0) {
                continue;
            }
            $this->createPage($cloudId, $name, $cfg['path'], $cfg['component'], $cfg['title'], $cfg['icon'], $cfg['sort'], 0);
        }
    }

    /** @return array<string, mixed> */
    private function metaM(string $title, string $icon, string $name, bool $withComponent = true, int $cache = 1): array
    {
        $meta = [
            'title' => $title,
            'i18n' => 'menu.' . $name,
            'icon' => $icon,
            'type' => 'M',
            'hidden' => 0,
            'breadcrumbEnable' => 1,
            'copyright' => 1,
            'cache' => $cache,
            'affix' => 0,
        ];
        if ($withComponent) {
            $meta['componentPath'] = 'modules/';
            $meta['componentSuffix'] = '.vue';
        }

        return $meta;
    }

    private function createGroup(int $parentId, string $name, string $title, string $icon, int $sort): Menu
    {
        return Menu::create([
            'name' => $name,
            'path' => '',
            'parent_id' => $parentId,
            'component' => '',
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'sort' => $sort,
            'meta' => $this->metaM($title, $icon, $name, false, 1),
        ]);
    }

    private function createPage(
        int $parentId,
        string $code,
        string $path,
        string $component,
        string $title,
        string $icon,
        int $sort,
        int $cache = 1,
    ): Menu {
        $pageName = array_key_exists($code, self::API_BUTTONS) ? $code . ':page' : $code;

        return Menu::create([
            'name' => $pageName,
            'path' => $path,
            'parent_id' => $parentId,
            'component' => $component,
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'sort' => $sort,
            'meta' => $this->metaM($title, $icon, $pageName, true, $cache),
        ]);
    }

    private function createButton(int $parentId, string $name, string $title): void
    {
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
}
