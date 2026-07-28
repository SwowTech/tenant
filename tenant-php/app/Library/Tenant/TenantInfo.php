<?php

declare(strict_types=1);

namespace App\Library\Tenant;

final class TenantInfo
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $domain,
        public readonly string $tablePrefix,
        public readonly int $status = 1,
    ) {}

    public static function tablePrefixForId(int $id): string
    {
        return 'cy_' . $id . '_';
    }
}
