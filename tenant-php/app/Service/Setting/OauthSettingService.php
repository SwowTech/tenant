<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Model\WechatAccount;
use App\Repository\SystemSettingRepository;

final class OauthSettingService
{
    public const KEY = 'global_oauth';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function get(): array
    {
        $cur = array_merge($this->defaults(), (array) $this->repo->get(self::KEY, []));
        $cur['wechat_accounts'] = $this->listWechatAccounts();

        return $cur;
    }

    public function save(array $data): array
    {
        $cur = array_merge($this->defaults(), (array) $this->repo->get(self::KEY, []));
        if (array_key_exists('account_id', $data)) {
            $cur['account_id'] = max(0, (int) $data['account_id']);
        }
        if (array_key_exists('host', $data)) {
            $cur['host'] = rtrim((string) $data['host'], '/');
        }
        $this->repo->set(self::KEY, ['account_id' => $cur['account_id'], 'host' => $cur['host']]);

        return $this->get();
    }

    private function defaults(): array
    {
        return [
            'account_id' => 0,
            'host' => '',
        ];
    }

    /** @return list<array{id:int,name:string,app_id:string}> */
    private function listWechatAccounts(): array
    {
        try {
            return WechatAccount::query()
                ->select(['id', 'name', 'app_id'])
                ->orderBy('id')
                ->get()
                ->map(static fn (WechatAccount $row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'app_id' => (string) $row->app_id,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
