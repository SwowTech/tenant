<?php

declare(strict_types=1);

use App\Model\Permission\Menu;
use Hyperf\Database\Seeders\Seeder;

class WechatMenu20260716 extends Seeder
{
    public function run(): void
    {
        if (Menu::query()->where('name', 'wechat')->exists()) {
            return;
        }

        $parent = Menu::create([
            'name' => 'wechat',
            'path' => '/wechat',
            'parent_id' => 0,
            'component' => '',
            'redirect' => '/wechat/account',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'meta' => [
                'title' => '微信公众号',
                'icon' => 'ri:wechat-line',
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

        $account = Menu::create([
            'name' => 'wechat:account',
            'path' => '/wechat/account',
            'parent_id' => $parent->id,
            'component' => 'base/views/wechat/account/index',
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
            'meta' => [
                'title' => '接入配置',
                'icon' => 'ri:settings-3-line',
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

        foreach ([
            'wechat:account:view' => '查看配置',
            'wechat:account:save' => '保存配置',
        ] as $name => $title) {
            Menu::create([
                'name' => $name,
                'path' => '',
                'parent_id' => $account->id,
                'component' => '',
                'redirect' => '',
                'created_by' => 0,
                'updated_by' => 0,
                'remark' => '',
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
}
