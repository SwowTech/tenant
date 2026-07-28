<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\SystemSetting;

final class SystemSettingRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = SystemSetting::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
