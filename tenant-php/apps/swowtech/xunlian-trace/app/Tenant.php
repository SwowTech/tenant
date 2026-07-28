<?php

declare(strict_types=1);

namespace TraceApp;

/**
 * 租户表前缀：优先网关 X-Tenant-Prefix，否则 cy_{id}_
 */
final class Tenant
{
    private static string $prefix = '';

    private static int $id = 0;

    public static function boot(int $tenantId, ?string $headerPrefix = null): void
    {
        self::$id = $tenantId;
        $p = is_string($headerPrefix) ? trim($headerPrefix) : '';
        if ($p === '') {
            $p = 'cy_' . $tenantId . '_';
        }
        if (! str_ends_with($p, '_')) {
            $p .= '_';
        }
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $p)) {
            throw new \InvalidArgumentException('invalid tenant prefix');
        }
        self::$prefix = $p;
    }

    public static function id(): int
    {
        return self::$id;
    }

    public static function prefix(): string
    {
        return self::$prefix;
    }

    /** @return non-empty-string 如 cy_33_trace_product */
    public static function table(string $logical): string
    {
        $logical = trim($logical, '_');
        if ($logical === '' || ! preg_match('/\A[a-z0-9_]+\z/', $logical)) {
            throw new \InvalidArgumentException('invalid table name');
        }
        if (! str_starts_with($logical, 'trace_')) {
            $logical = 'trace_' . $logical;
        }

        return self::$prefix . $logical;
    }

    public static function quote(string $logical): string
    {
        return '`' . str_replace('`', '``', self::table($logical)) . '`';
    }
}
