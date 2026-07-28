<?php

declare(strict_types=1);

namespace App\Library\Tenant;

use Hyperf\Context\Context;

final class TenantContext
{
    private const KEY = 'tenant.context';

    public static function set(TenantInfo $tenant): void
    {
        Context::set(self::KEY, $tenant);
    }

    public static function get(): ?TenantInfo
    {
        return Context::get(self::KEY);
    }

    public static function id(): ?int
    {
        return self::get()?->id;
    }

    public static function tablePrefix(): string
    {
        return self::get()?->tablePrefix ?? '';
    }

    public static function clear(): void
    {
        Context::destroy(self::KEY);
    }
}
