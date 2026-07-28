<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use App\Repository\SystemSettingRepository;

class CloudSiteSettingService
{
    public const SETTING_KEY = 'cloud.site';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function getSite(): array
    {
        $raw = $this->repo->get(self::SETTING_KEY, []);
        if (! is_array($raw)) {
            $raw = [];
        }

        return array_merge([
            'key' => '',
            'token' => '',
            'url' => '',
            'username' => '',
            'phone' => '',
            'email' => '',
            'bound_at' => '',
        ], $raw);
    }

    public function isBound(): bool
    {
        $site = $this->getSite();

        return $site['key'] !== '' && $site['token'] !== '';
    }

    public function saveFromPush(array $payload): void
    {
        $key = trim((string) ($payload['key'] ?? ''));
        $token = trim((string) ($payload['token'] ?? ''));
        if ($key === '' || $token === '') {
            throw new \InvalidArgumentException('key/token 不能为空');
        }
        $this->repo->set(self::SETTING_KEY, [
            'key' => $key,
            'token' => $token,
            'url' => (string) ($payload['url'] ?? ''),
            'username' => (string) ($payload['username'] ?? ''),
            'phone' => (string) ($payload['phone'] ?? ''),
            'email' => (string) ($payload['email'] ?? ''),
            'bound_at' => date('c'),
        ]);
    }

    public function buildAuthUrl(string $forward = 'profile'): array
    {
        $passport = rtrim((string) env('SAAS_PASSPORT_URL', ''), '/');
        if ($passport === '') {
            $passport = rtrim((string) env('SAAS_ADMIN_URL', 'http://127.0.0.1:5174'), '/') . '/platform/passport';
        }
        $site = $this->getSite();
        $siteUrl = $site['url'] !== '' ? $site['url'] : \App\Library\Support\AppUrl::publicBase();
        $auth = [
            'key' => $site['key'],
            'password' => ($site['key'] !== '' && $site['token'] !== '')
                ? md5($site['key'] . $site['token']) : '',
            'url' => $siteUrl,
            'version' => (string) env('APP_VERSION', '1.0.0'),
            'forward' => $forward,
        ];
        $query = base64_encode(json_encode($auth, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return [
            'url' => $passport . (str_contains($passport, '?') ? '&' : '?') . '__auth=' . urlencode($query),
            'bound' => $this->isBound(),
        ];
    }

    public function siteInfoForAdmin(): array
    {
        $site = $this->getSite();
        $token = $site['token'];
        $masked = $token === '' ? '' : (substr($token, 0, 4) . '****' . substr($token, -4));
        return [
            'bound' => $this->isBound(),
            'key' => $site['key'],
            'token_masked' => $masked,
            'url' => $site['url'],
            'username' => $site['username'],
            'phone' => $site['phone'],
            'email' => $site['email'],
            'bound_at' => $site['bound_at'],
            'auth' => $this->buildAuthUrl(),
        ];
    }

    public function clearSite(): void
    {
        $this->repo->set(self::SETTING_KEY, [
            'key' => '',
            'token' => '',
            'url' => '',
            'username' => '',
            'phone' => '',
            'email' => '',
            'bound_at' => '',
        ]);
    }

    public function getTokenPlain(): string
    {
        return (string) $this->getSite()['token'];
    }

    /**
     * 向 saas 换取商城 store_token，供打开 /platform/app-store 时注入.
     *
     * @return array{bound: bool, store_token?: string, store_url?: string, message?: string}
     */
    public function issueStoreTokenForAdmin(): array
    {
        if (! $this->isBound()) {
            return [
                'bound' => false,
                'message' => '请先完成云站点绑定',
            ];
        }
        $site = $this->getSite();
        $saas = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        $admin = rtrim((string) env('SAAS_ADMIN_URL', 'http://127.0.0.1:5174'), '/');
        $url = $saas . '/cloud/passport/store-token';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'key' => $site['key'],
                'token' => $site['token'],
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code < 200 || $code >= 300) {
            return [
                'bound' => true,
                'message' => $err !== '' ? ('换取商城令牌失败：' . $err) : '换取商城令牌失败',
            ];
        }
        $body = json_decode((string) $raw, true);
        $token = is_array($body) ? (string) ($body['data']['store_token'] ?? '') : '';
        if ($token === '') {
            $msg = is_array($body) ? (string) ($body['message'] ?? '换取商城令牌失败') : '换取商城令牌失败';

            return ['bound' => true, 'message' => $msg];
        }

        return [
            'bound' => true,
            'store_token' => $token,
            'store_url' => $admin . '/platform/app-store?store_token=' . rawurlencode($token),
        ];
    }
}
