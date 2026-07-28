<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Repository\SystemSettingRepository;

final class IpWhitelistSettingService
{
    public const KEY = 'ip_white_list';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function get(): array
    {
        return ['list' => $this->list()];
    }

    /** @return list<string> */
    public function list(): array
    {
        $stored = $this->repo->get(self::KEY, []);
        if (! is_array($stored)) {
            return [];
        }

        $list = [];
        foreach ($stored as $item) {
            $ip = trim((string) $item);
            if ($ip !== '' && ! in_array($ip, $list, true)) {
                $list[] = $ip;
            }
        }

        return $list;
    }

    /** @param list<string> $list */
    public function save(array $data): array
    {
        $list = isset($data['list']) && is_array($data['list']) ? $data['list'] : [];
        $normalized = [];
        foreach ($list as $item) {
            $ip = trim((string) $item);
            if ($ip !== '' && ! in_array($ip, $normalized, true)) {
                $normalized[] = $ip;
            }
        }
        $this->repo->set(self::KEY, $normalized);

        return ['list' => $normalized];
    }
}
