<?php

declare(strict_types=1);

namespace App\Library\Storage;

use App\Library\Tenant\TenantContext;

/**
 * 本地 / OSS 统一对象前缀：tenant/{tenantId}/app/{appKey}/...
 * 便于多租户隔离，后续上 OSS 时沿用同一 key 结构。
 */
final class TenantStoragePath
{
    public const PLATFORM_APP = 'platform';

    public static function appKey(?string $identifier = null): string
    {
        $id = trim(str_replace('\\', '/', (string) $identifier));
        if ($id === '') {
            return self::PLATFORM_APP;
        }
        $key = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $id) ?? self::PLATFORM_APP;

        return $key !== '' ? $key : self::PLATFORM_APP;
    }

    /**
     * 相对 uploads 根的目录，如 tenant/31/app/platform/2026-07-27
     * 或 tenant/31/app/swowtech_xunlian-trace-tp6/product
     */
    public static function relative(
        ?int $tenantId = null,
        ?string $appIdentifier = null,
        string $category = '',
        ?string $dateSegment = null,
    ): string {
        if ($tenantId === null) {
            $tenantId = TenantContext::id() ?? 0;
        }
        $parts = [
            'tenant',
            (string) max(0, $tenantId),
            'app',
            self::appKey($appIdentifier),
        ];
        $category = trim(str_replace(['\\', '..'], ['/', ''], $category), '/');
        if ($category !== '') {
            $parts[] = $category;
        }
        if ($dateSegment !== null && $dateSegment !== '') {
            $parts[] = $dateSegment;
        }

        return implode('/', $parts);
    }

    /** OSS / Flysystem 对象完整 key（含文件名） */
    public static function objectKey(
        string $filename,
        ?int $tenantId = null,
        ?string $appIdentifier = null,
        string $category = '',
        ?string $dateSegment = null,
    ): string {
        $dir = self::relative($tenantId, $appIdentifier, $category, $dateSegment);

        return $dir . '/' . ltrim($filename, '/');
    }
}
