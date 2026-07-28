<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use GuzzleHttp\Client;

final class CloudDiagnoseService
{
    public function __construct(private readonly CloudSiteSettingService $site) {}

    public function diagnose(): array
    {
        $info = $this->site->siteInfoForAdmin();
        $site = $this->site->getSite();

        return [
            'bound' => $this->site->isBound(),
            'site' => [
                'url' => $site['url'] !== '' ? $site['url'] : \App\Library\Support\AppUrl::publicBase(),
                'key' => $site['key'],
                'token_masked' => $info['token_masked'],
                'token' => '',
                'version' => (string) env('APP_VERSION', '1.0.0'),
                'username' => $site['username'],
                'phone' => $site['phone'],
                'email' => $site['email'] ?? '',
                'bound_at' => $site['bound_at'],
            ],
            'network' => [
                'server_time' => date('c'),
                'saas' => $this->pingSaas(),
            ],
            'register_path' => '/setting/cloud/register',
        ];
    }

    /**
     * @return array{ok: bool, url: string, latency_ms: int, message: string}
     */
    public function pingSaas(): array
    {
        $base = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        $url = $base . '/cloud/passport/ping';
        $t0 = microtime(true);
        try {
            $client = new Client(['timeout' => 5, 'http_errors' => true]);
            $client->get($url);
            $ms = (int) round((microtime(true) - $t0) * 1000);

            return ['ok' => true, 'url' => $url, 'latency_ms' => $ms, 'message' => ''];
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $t0) * 1000);

            return ['ok' => false, 'url' => $url, 'latency_ms' => $ms, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, register_path: string, message: string}
     */
    public function reset(): array
    {
        if (! $this->site->isBound()) {
            throw new \InvalidArgumentException('当前未注册云站点，无需重置');
        }
        $site = $this->site->getSite();
        $this->revokeOnSaas((string) $site['key'], (string) $site['url']);
        $this->site->clearSite();

        return [
            'ok' => true,
            'register_path' => '/setting/cloud/register',
            'message' => '重置成功，请重新注册站点',
        ];
    }

    private function revokeOnSaas(string $key, string $siteUrl): void
    {
        $base = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        $token = (string) config('internal.token', '');
        $url = $base . '/internal/cloud/site-revoke';

        try {
            $client = new Client(['timeout' => 5, 'http_errors' => false]);
            $resp = $client->post($url, [
                'headers' => [
                    'X-Internal-Token' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'key' => $key,
                    'site_url' => $siteUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('作废云站点失败: ' . $e->getMessage(), 0, $e);
        }

        $body = json_decode((string) $resp->getBody(), true);
        $code = is_array($body) ? (int) ($body['code'] ?? 0) : 0;
        if ($code !== 200) {
            $msg = is_array($body) ? (string) ($body['message'] ?? 'unknown') : 'invalid response';
            throw new \RuntimeException('作废云站点失败: ' . $msg);
        }
    }
}
