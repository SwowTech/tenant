<?php
declare(strict_types=1);

namespace App\Library\Support;

final class AppUrl
{
    private static ?string $rawAppUrl = null;
    private static ?int $serverPort = null;
    private static ?string $scheme = null;
    private static ?string $host = null;

    public static function reset(): void
    {
        self::$rawAppUrl = null;
        self::$serverPort = null;
        self::$scheme = null;
        self::$host = null;
    }

    public static function configure(?string $appUrl, ?int $serverPort): void
    {
        self::reset();
        self::$rawAppUrl = $appUrl;
        self::$serverPort = $serverPort;
        self::parse();
    }

    private static function ensureParsed(): void
    {
        if (self::$scheme !== null && self::$host !== null) {
            return;
        }
        if (self::$rawAppUrl === null) {
            self::$rawAppUrl = (string) \Hyperf\Support\env('APP_URL', 'http://127.0.0.1');
        }
        if (self::$serverPort === null) {
            self::$serverPort = (int) \Hyperf\Support\env('SERVER_PORT', 9501);
        }
        self::parse();
    }

    private static function parse(): void
    {
        $raw = rtrim(trim((string) self::$rawAppUrl), '/');
        if ($raw === '') {
            $raw = 'http://127.0.0.1';
        }
        if (! str_contains($raw, '://')) {
            $raw = 'http://' . $raw;
        }
        $parts = parse_url($raw);
        self::$scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        self::$host = (string) ($parts['host'] ?? '127.0.0.1');
        // URL 内端口忽略，改由 SERVER_PORT / publicBase 决定
        if (self::$serverPort === null) {
            self::$serverPort = 9501;
        }
    }

    public static function scheme(): string
    {
        self::ensureParsed();
        return self::$scheme ?? 'http';
    }

    public static function host(): string
    {
        self::ensureParsed();
        return self::$host ?? '127.0.0.1';
    }

    public static function publicBase(): string
    {
        self::ensureParsed();
        $base = self::scheme() . '://' . self::host();
        $port = (int) self::$serverPort;
        if (in_array($port, [80, 443], true)) {
            return $base;
        }
        $host = self::host();
        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return $base . ':' . $port;
        }
        return $base;
    }

    public static function tenantAccessUrl(string $domain, string $customDomain = ''): string
    {
        self::ensureParsed();
        $custom = trim($customDomain);
        if ($custom !== '') {
            if (str_contains($custom, '://')) {
                return rtrim($custom, '/');
            }

            return self::withLocalPort(self::scheme() . '://' . $custom);
        }
        $label = trim($domain);
        if ($label === '') {
            return '';
        }
        // *.127.0.0.1 浏览器无法解析；本地统一用 *.localhost
        $apex = self::host();
        if ($apex === '127.0.0.1') {
            $apex = 'localhost';
        }

        return self::withLocalPort(self::scheme() . '://' . $label . '.' . $apex);
    }

    /**
     * 应用网关入口（带租户子域，避免 apex 访问出现 unknown tenant）.
     */
    public static function appOpenUrl(string $identifier, string $tenantDomain, string $customDomain = ''): string
    {
        $base = self::tenantAccessUrl($tenantDomain, $customDomain);
        if ($base === '') {
            $base = self::publicBase();
        }

        return rtrim($base, '/') . '/' . ltrim($identifier, '/') . '/';
    }

    /**
     * 本地开发端口：localhost / *.localhost 等非 80/443 时追加 SERVER_PORT.
     */
    public static function withLocalPort(string $base): string
    {
        self::ensureParsed();
        $port = (int) self::$serverPort;
        if (in_array($port, [80, 443], true)) {
            return $base;
        }
        $host = parse_url($base, PHP_URL_HOST) ?: '';
        $isLocal = $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || $host === '127.0.0.1'
            || str_ends_with($host, '.127.0.0.1');
        if ($isLocal) {
            return $base . ':' . $port;
        }

        return $base;
    }
}
