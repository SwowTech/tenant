<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use App\Exception\BusinessException;
use App\Service\App\AppInstallService;
use GuzzleHttp\Client;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Mine\AppStore\Plugin;
use Plugin\MineAdmin\AppStore\Service\Service as AppStoreService;

use function Hyperf\Support\env;

final class CloudAppInstallService
{
    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, message: string}
     */
    public function install(array $payload): array
    {
        $siteKey = trim((string) ($payload['site_key'] ?? ''));
        $domain = trim((string) ($payload['domain'] ?? ''));
        $appIdentifier = trim((string) ($payload['app_identifier'] ?? ''));
        $version = trim((string) ($payload['version'] ?? ''));
        $packageUrl = trim((string) ($payload['package_url'] ?? ''));
        $packageHash = strtolower(trim((string) ($payload['package_hash'] ?? '')));

        if ($siteKey === '' || $domain === '' || $appIdentifier === '' || $packageUrl === '' || $packageHash === '') {
            return ['ok' => false, 'message' => '安装参数不完整'];
        }

        try {
            $this->assertPackageUrlAllowed($packageUrl);
            $this->verifyLicenseWithSaas($siteKey, $domain, $appIdentifier);
            $zipPath = $this->downloadAndVerifyHash($packageUrl, $packageHash);

            try {
                if ($this->shouldSkipPluginInstall($payload)) {
                    return ['ok' => true, 'message' => 'hash_ok_skip_install'];
                }

                $this->installFromZip($zipPath, $appIdentifier);
            } finally {
                if (is_file($zipPath)) {
                    @unlink($zipPath);
                }
            }
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (BusinessException $e) {
            if ($this->isAlreadyInstalled($e)) {
                return ['ok' => true, 'message' => 'already_installed'];
            }

            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'ok'];
    }

    public function assertPackageUrlAllowed(string $packageUrl): void
    {
        $host = parse_url($packageUrl, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            throw new \InvalidArgumentException('package_url 无效');
        }

        $allowlist = $this->packageUrlAllowlist();
        if ($allowlist === []) {
            throw new \InvalidArgumentException('未配置 STORE_PACKAGE_URL_ALLOWLIST');
        }

        $hostLower = strtolower($host);
        foreach ($allowlist as $allowed) {
            if ($hostLower === strtolower($allowed)) {
                return;
            }
        }

        throw new \InvalidArgumentException('package_url host 不在白名单');
    }

    /**
     * @return list<string>
     */
    public function packageUrlAllowlist(): array
    {
        $raw = (string) env('STORE_PACKAGE_URL_ALLOWLIST', '');
        $parts = array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== '');

        return array_values($parts);
    }

    public function verifyLicenseWithSaas(string $siteKey, string $domain, string $appIdentifier): void
    {
        $base = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        if ($base === '') {
            throw new \RuntimeException('未配置 SAAS_PHP_PUBLIC_URL');
        }

        $token = (string) config('internal.token', '');
        if ($token === '') {
            throw new \RuntimeException('未配置 INTERNAL_API_TOKEN');
        }

        $query = http_build_query([
            'site_key' => $siteKey,
            'domain' => $domain,
            'app_identifier' => $appIdentifier,
        ]);
        $url = $base . '/store/license/verify?' . $query;

        try {
            $client = new Client(['timeout' => 15, 'http_errors' => false]);
            $resp = $client->get($url, [
                'headers' => [
                    'X-Internal-Token' => $token,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('授权校验失败: ' . $e->getMessage(), 0, $e);
        }

        $body = json_decode((string) $resp->getBody(), true);
        if (! is_array($body)) {
            throw new \RuntimeException('授权校验响应无效');
        }

        $code = (int) ($body['code'] ?? 0);
        if ($code === 401 || $code === 403) {
            throw new \RuntimeException('授权校验未通过: ' . (string) ($body['message'] ?? 'unauthorized'));
        }
        if ($code !== 200) {
            throw new \RuntimeException('授权校验失败: ' . (string) ($body['message'] ?? 'unknown'));
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        if (($data['ok'] ?? false) !== true) {
            throw new \RuntimeException('未授权: ' . (string) ($data['message'] ?? '无有效授权'));
        }
    }

    public function downloadAndVerifyHash(string $packageUrl, string $expectedSha256): string
    {
        $expectedSha256 = strtolower(trim($expectedSha256));
        if ($expectedSha256 === '' || ! preg_match('/\A[a-f0-9]{64}\z/', $expectedSha256)) {
            throw new \InvalidArgumentException('package_hash 无效');
        }

        $tmpDir = BASE_PATH . '/runtime/tmp';
        if (! is_dir($tmpDir) && ! mkdir($tmpDir, 0775, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('无法创建临时目录');
        }

        $zipPath = $tmpDir . '/cloud-app-' . bin2hex(random_bytes(8)) . '.zip';

        try {
            $client = new Client([
                'timeout' => 120,
                'http_errors' => false,
                'allow_redirects' => false,
                'sink' => $zipPath,
            ]);
            $resp = $client->get($packageUrl);
            $status = $resp->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('下载应用包失败 HTTP ' . $status);
            }
        } catch (\RuntimeException $e) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            throw $e;
        } catch (\Throwable $e) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            throw new \RuntimeException('下载应用包失败: ' . $e->getMessage(), 0, $e);
        }

        if (! is_file($zipPath)) {
            throw new \RuntimeException('应用包未保存成功');
        }

        $actual = hash_file('sha256', $zipPath);
        if (! is_string($actual) || ! hash_equals($expectedSha256, strtolower($actual))) {
            @unlink($zipPath);
            throw new \RuntimeException('应用包 hash 校验失败');
        }

        return $zipPath;
    }

    private function installFromZip(string $zipPath, string $appIdentifier): void
    {
        /** @var AppInstallService $appInstall */
        $appInstall = make(AppInstallService::class);
        if ($appInstall->zipLooksLikeApp($zipPath)) {
            $appInstall->installFromZip($zipPath, $appIdentifier);

            return;
        }

        $pluginPath = Plugin::PLUGIN_PATH . '/' . $appIdentifier;
        if (is_file($pluginPath . '/install.lock')) {
            return;
        }

        $uploaded = new UploadedFile(
            $zipPath,
            (int) (filesize($zipPath) ?: 0),
            \UPLOAD_ERR_OK,
            basename($zipPath),
            'application/zip'
        );

        /** @var AppStoreService $appStore */
        $appStore = make(AppStoreService::class);

        try {
            $appStore->uploadLocalApp($uploaded);
        } catch (BusinessException $e) {
            if ($this->isAlreadyInstalled($e)) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function shouldSkipPluginInstall(array $payload): bool
    {
        if (empty($payload['_skip_plugin_install'])) {
            return false;
        }

        return env('APP_ENV') === 'testing'
            || env('CLOUD_APP_INSTALL_ALLOW_SKIP') === true
            || env('CLOUD_APP_INSTALL_ALLOW_SKIP') === 'true';
    }

    private function isAlreadyInstalled(BusinessException $e): bool
    {
        $msg = $e->getMessage();
        if ($msg === '') {
            return false;
        }

        return str_contains($msg, '已安装')
            || str_contains(strtolower($msg), 'installed')
            || str_contains($msg, 'app_installed');
    }
}
