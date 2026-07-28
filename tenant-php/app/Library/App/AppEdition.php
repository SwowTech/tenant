<?php

declare(strict_types=1);

namespace App\Library\App;

final class AppEdition
{
    public static function normalizeEdition(mixed $raw): string
    {
        if (! is_string($raw)) {
            return '';
        }
        return strtolower(trim($raw));
    }

    public static function editionFromManifest(array $manifest): string
    {
        return self::normalizeEdition($manifest['edition'] ?? '');
    }

    public static function familyFromManifest(array $manifest, string $identifier): string
    {
        $family = trim((string) ($manifest['family'] ?? ''));
        return $family !== '' ? $family : $identifier;
    }

    /** @return list<string> */
    public static function upgradesFromFromManifest(array $manifest): array
    {
        $raw = $manifest['upgrades_from'] ?? [];
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $id) {
            if (is_string($id) && trim($id) !== '') {
                $out[] = trim($id);
            }
        }
        return array_values(array_unique($out));
    }

    public static function migrateRelativePath(array $manifest): ?string
    {
        $path = $manifest['migrate'] ?? null;
        return is_string($path) && trim($path) !== '' ? trim($path) : null;
    }

    /** 自动分配给新租户时：无 edition 或 community 才自动装 */
    public static function shouldAutoAssign(array $manifest): bool
    {
        $edition = self::editionFromManifest($manifest);
        return $edition === '' || $edition === 'community';
    }

    /**
     * 写入 tenant_installed_app 时填充 edition/family；磁盘无包时 edition=''、family=identifier.
     *
     * @param array<string, mixed> $payload
     */
    public static function fillEditionFields(array &$payload, string $identifier): void
    {
        try {
            $meta = AppManifest::editionMeta($identifier);
            $payload['edition'] = $meta['edition'];
            $payload['family'] = $meta['family'];
        } catch (\Throwable) {
            $payload['edition'] = '';
            $payload['family'] = $identifier;
        }
    }
}
