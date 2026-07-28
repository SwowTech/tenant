<?php
declare(strict_types=1);

namespace App\Service\Welcome;

use Hyperf\DbConnection\Db;

final class WelcomeMarketService
{
    public function apps(array $params): array
    {
        $q = Db::connection('platform')->table('market_app')->where('status', 'active');
        $keyword = trim((string) ($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $q->where(function ($w) use ($keyword) {
                $w->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('identifier', 'like', '%' . $keyword . '%');
            });
        }
        $sort = (string) ($params['sort'] ?? 'default');
        match ($sort) {
            'newest' => $q->orderByDesc('id'),
            'hot', 'download' => $q->orderByDesc('id'), // 无下载数字段时回退
            default => $q->orderByDesc('id'),
        };
        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = max(1, min(50, (int) ($params['page_size'] ?? 12)));
        $total = (clone $q)->count();
        $rows = $q->forPage($page, $pageSize)->get([
            'id', 'identifier', 'title', 'edition', 'family', 'description', 'cover_url', 'category', 'price_type', 'price', 'status',
        ]);
        $list = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            $item['edition'] = (string) ($item['edition'] ?? '');
            $item['family'] = (string) (($item['family'] ?? '') !== '' ? $item['family'] : ($item['identifier'] ?? ''));
            $list[] = $item;
        }

        return [
            'list' => $list,
            'total' => $total,
            'groups' => $this->groupByFamily($list),
        ];
    }

    public function stats(): array
    {
        $available = (int) Db::connection('platform')->table('market_app')->where('status', 'active')->count();
        $totalApps = (int) Db::connection('platform')->table('market_app')->count();
        $totalVersions = 0;
        try {
            $totalVersions = (int) Db::connection('platform')->table('market_app_version')->count();
        } catch (\Throwable) {
            $totalVersions = $totalApps;
        }
        return [
            'total_apps' => $totalApps,
            'available_apps' => $available,
            'total_versions' => $totalVersions,
        ];
    }

    /**
     * @param list<array<string, mixed>> $list
     * @return list<array{family: string, editions: list<array<string, mixed>>}>
     */
    private function groupByFamily(array $list): array
    {
        $groups = [];
        foreach ($list as $item) {
            $family = (string) ($item['family'] ?? $item['identifier'] ?? '');
            if ($family === '') {
                continue;
            }
            if (! isset($groups[$family])) {
                $groups[$family] = ['family' => $family, 'editions' => []];
            }
            $groups[$family]['editions'][] = $item;
        }

        return array_values($groups);
    }
}
