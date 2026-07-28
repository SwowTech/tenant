<?php

declare(strict_types=1);

namespace App\Service\Welcome;

use App\Library\Cloud\SaasPublicClient;
use Throwable;

final class WelcomeMarketService
{
    public function apps(array $params): array
    {
        $query = [
            'page' => max(1, (int) ($params['page'] ?? 1)),
            'page_size' => max(1, min(50, (int) ($params['page_size'] ?? 12))),
            'group_by_family' => 1,
        ];
        $keyword = trim((string) ($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query['keyword'] = $keyword;
        }

        try {
            $data = SaasPublicClient::get('/store/apps', $query);
        } catch (Throwable $e) {
            return [
                'list' => [],
                'total' => 0,
                'groups' => [],
                'message' => $e->getMessage(),
            ];
        }

        $list = [];
        foreach (($data['list'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = [
                'id' => (int) ($row['id'] ?? 0),
                'identifier' => (string) ($row['identifier'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'edition' => (string) ($row['edition'] ?? ''),
                'family' => (string) (($row['family'] ?? '') !== '' ? $row['family'] : ($row['identifier'] ?? '')),
                'description' => (string) ($row['description'] ?? ''),
                'cover_url' => (string) ($row['cover_url'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'price_type' => (string) ($row['price_type'] ?? ''),
                'price' => $row['price'] ?? 0,
                'status' => (string) ($row['status'] ?? 'active'),
            ];
            $list[] = $item;
        }

        $groups = $data['groups'] ?? null;
        if (! is_array($groups)) {
            $groups = $this->groupByFamily($list);
        }

        return [
            'list' => $list,
            'total' => (int) ($data['total'] ?? count($list)),
            'groups' => $groups,
        ];
    }

    public function stats(): array
    {
        try {
            $data = SaasPublicClient::get('/store/stats');

            return [
                'total_apps' => (int) ($data['total_apps'] ?? 0),
                'available_apps' => (int) ($data['available_apps'] ?? 0),
                'total_versions' => (int) ($data['total_versions'] ?? 0),
            ];
        } catch (Throwable) {
            return [
                'total_apps' => 0,
                'available_apps' => 0,
                'total_versions' => 0,
            ];
        }
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
