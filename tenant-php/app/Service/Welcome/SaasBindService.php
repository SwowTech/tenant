<?php
declare(strict_types=1);

namespace App\Service\Welcome;

use App\Service\Cloud\CloudSiteSettingService;

final class SaasBindService
{
    public function __construct(private readonly CloudSiteSettingService $cloudSite) {}

    public function status(): array
    {
        $bindUrl = '/setting/cloud/register';

        if (! $this->cloudSite->isBound()) {
            return [
                'bound' => false,
                'site' => null,
                'tenant' => null,
                'message' => '您还未注册云站点（可选）。注册后可使用云升级、应用订阅等能力',
                'bind_url' => $bindUrl,
            ];
        }

        $site = $this->cloudSite->getSite();

        return [
            'bound' => true,
            'site' => [
                'key' => (string) $site['key'],
                'username' => (string) $site['username'],
                'phone' => (string) $site['phone'],
                'email' => (string) ($site['email'] ?? ''),
                'url' => (string) $site['url'],
                'bound_at' => (string) $site['bound_at'],
            ],
            'tenant' => null,
            'message' => '已注册云站点',
            'bind_url' => $bindUrl,
        ];
    }
}
