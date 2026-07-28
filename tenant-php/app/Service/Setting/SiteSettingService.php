<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Model\SiteIcp;
use App\Repository\SystemSettingRepository;

final class SiteSettingService
{
    public const KEY = 'site';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function get(): array
    {
        $raw = (array) $this->repo->get(self::KEY, []);

        return [
            'closed' => (bool) ($raw['closed'] ?? false),
            'close_reason' => (string) ($raw['close_reason'] ?? ''),
            'auto_logout' => max(0, (int) ($raw['auto_logout'] ?? 0)),
        ];
    }

    public function save(array $data): array
    {
        $cur = $this->get();
        if (array_key_exists('closed', $data)) {
            $cur['closed'] = (bool) $data['closed'];
        }
        if (array_key_exists('close_reason', $data)) {
            $cur['close_reason'] = (string) $data['close_reason'];
        }
        if (array_key_exists('auto_logout', $data)) {
            $cur['auto_logout'] = max(0, (int) $data['auto_logout']);
        }
        // Persist only useful fields
        $this->repo->set(self::KEY, [
            'closed' => (bool) $cur['closed'],
            'close_reason' => (string) $cur['close_reason'],
            'auto_logout' => (int) $cur['auto_logout'],
        ]);

        return $this->get();
    }

    public function listIcp(string $keyword = ''): array
    {
        $q = SiteIcp::query()->orderByDesc('id');
        if ($keyword !== '') {
            $q->where('domain', 'like', '%' . $keyword . '%');
        }

        return ['list' => $q->limit(100)->get(), 'total' => $q->count()];
    }

    public function createIcp(array $data): SiteIcp
    {
        $row = new SiteIcp();
        $row->fill([
            'domain' => (string) ($data['domain'] ?? ''),
            'icp' => (string) ($data['icp'] ?? ''),
            'police' => (string) ($data['police'] ?? ''),
            'license_url' => (string) ($data['license_url'] ?? ''),
        ]);
        $row->save();

        return $row;
    }

    public function updateIcp(int $id, array $data): SiteIcp
    {
        $row = SiteIcp::query()->find($id);
        if ($row === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '备案记录不存在');
        }
        foreach (['domain', 'icp', 'police', 'license_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $row->{$field} = (string) $data[$field];
            }
        }
        $row->save();

        return $row;
    }

    public function deleteIcp(int $id): void
    {
        SiteIcp::query()->where('id', $id)->delete();
    }
}
