<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Tenant\TenantContext;
use App\Model\WechatAccount;
use GuzzleHttp\Client;
use Hyperf\Redis\Redis;

final class WechatAccessTokenService
{
    public function __construct(private readonly Redis $redis) {}

    /**
     * @return array{access_token: string, expires_in: int}
     */
    public function getToken(WechatAccount $account, bool $forceRefresh = false): array
    {
        if ($account->app_id === '' || $account->app_secret === '') {
            throw new BusinessException(ResultCode::FAIL, '请先配置 AppID 与 AppSecret');
        }

        $tenantId = TenantContext::get()?->id ?? 0;
        $key = sprintf('wechat:access_token:%d:%s', $tenantId, $account->app_id);

        if (! $forceRefresh) {
            $cached = $this->redis->get($key);
            if (is_string($cached) && $cached !== '') {
                $ttl = (int) $this->redis->ttl($key);
                return [
                    'access_token' => $cached,
                    'expires_in' => max($ttl, 0),
                ];
            }
        }

        $client = new Client(['timeout' => 10]);
        $resp = $client->get('https://api.weixin.qq.com/cgi-bin/token', [
            'query' => [
                'grant_type' => 'client_credential',
                'appid' => $account->app_id,
                'secret' => $account->app_secret,
            ],
        ]);
        $data = json_decode((string) $resp->getBody(), true) ?: [];
        if (empty($data['access_token'])) {
            $msg = (string) ($data['errmsg'] ?? '获取 access_token 失败');
            throw new BusinessException(ResultCode::FAIL, $msg);
        }

        $expiresIn = (int) ($data['expires_in'] ?? 7200);
        $ttl = max($expiresIn - 300, 60);
        $this->redis->setex($key, $ttl, $data['access_token']);

        return [
            'access_token' => (string) $data['access_token'],
            'expires_in' => $ttl,
        ];
    }
}
