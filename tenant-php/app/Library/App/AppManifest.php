<?php

declare(strict_types=1);

namespace App\Library\App;

use RuntimeException;

final class AppManifest
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $identifier): array
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $dir = AppPath::appDir($identifier);
        $file = $dir . '/app.json';
        if (! is_file($file)) {
            throw new RuntimeException("App manifest not found: {$identifier}");
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new RuntimeException("Unable to read app manifest: {$identifier}");
        }

        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid app.json for {$identifier}");
        }

        if (($decoded['name'] ?? '') !== $identifier) {
            throw new RuntimeException("app.json name must match identifier {$identifier}");
        }

        if (! is_string($decoded['version'] ?? null) || trim((string) $decoded['version']) === '') {
            throw new RuntimeException("app.json missing version for {$identifier}");
        }

        $webPath = $decoded['web']['path'] ?? null;
        if (! is_string($webPath) || trim($webPath) === '') {
            throw new RuntimeException("app.json missing web.path for {$identifier}");
        }

        return $decoded;
    }

    /**
     * @return array{edition: string, family: string, upgrades_from: list<string>, migrate: ?string, pricing: ?array}
     */
    public static function editionMeta(string $identifier): array
    {
        $m = self::load($identifier);
        return [
            'edition' => AppEdition::editionFromManifest($m),
            'family' => AppEdition::familyFromManifest($m, $identifier),
            'upgrades_from' => AppEdition::upgradesFromFromManifest($m),
            'migrate' => AppEdition::migrateRelativePath($m),
            'pricing' => is_array($m['pricing'] ?? null) ? $m['pricing'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public static function webDir(array $manifest, string $identifier): string
    {
        $relative = (string) ($manifest['web']['path'] ?? 'web');

        return AppPath::appDir($identifier) . '/' . trim($relative, '/\\');
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public static function apiPrefix(array $manifest): string
    {
        $prefix = (string) ($manifest['api_prefix'] ?? 'api');

        return trim($prefix, '/');
    }

    /**
     * 传统 PHP 应用：除静态资源外整站反代到应用进程。
     *
     * @param array<string, mixed> $manifest
     */
    public static function proxyAll(array $manifest): bool
    {
        if (! empty($manifest['proxy_all'])) {
            return true;
        }
        if (array_key_exists('api_prefix', $manifest) && trim((string) $manifest['api_prefix']) === '') {
            return true;
        }

        return false;
    }

    /**
     * proxy_all 时可直接读盘的静态扩展名。
     */
    public static function isStaticAssetPath(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return false;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, [
            'css', 'js', 'mjs', 'map',
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
            'woff', 'woff2', 'ttf', 'eot',
            'mp4', 'webm', 'mp3', 'wav',
            'pdf', 'txt', 'xml', 'json',
        ], true);
    }
}
