<?php

declare(strict_types=1);

namespace App\Library\Cloud;

use RuntimeException;
use Throwable;

use function Hyperf\Support\env;

/**
 * 租户端调用 Cloud（saas-php）公开 HTTP API，禁止直连 platform_db.
 */
final class SaasPublicClient
{
    public static function baseUrl(): string
    {
        return rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @return array<string, mixed>
     */
    public static function get(string $path, array $query = [], int $timeout = 10): array
    {
        $base = self::baseUrl();
        if ($base === '') {
            throw new RuntimeException('未配置 SAAS_PHP_PUBLIC_URL');
        }
        $path = '/' . ltrim($path, '/');
        $url = $base . $path;
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('无法初始化 HTTP 客户端');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException($err !== '' ? $err : '请求云平台失败');
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('云平台 HTTP ' . $code);
        }

        try {
            $json = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('云平台响应不是合法 JSON: ' . $e->getMessage());
        }
        if (! is_array($json)) {
            throw new RuntimeException('云平台响应格式错误');
        }

        $data = $json['data'] ?? $json;
        if (! is_array($data)) {
            throw new RuntimeException('云平台 data 格式错误');
        }

        return $data;
    }
}
