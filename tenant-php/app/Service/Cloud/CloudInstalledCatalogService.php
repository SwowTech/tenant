<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use App\Library\App\AppEdition;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use Hyperf\DbConnection\Db;
use Mine\AppStore\Plugin;
use Throwable;

/**
 * 应用管理页：本地插件/应用清单 + 云市场来源与版本对比.
 */
final class CloudInstalledCatalogService
{
    /**
     * @return array{list: list<array<string, mixed>>, remote_ok: bool, remote_message: string}
     */
    public function list(): array
    {
        $items = [];
        $items = array_merge($items, $this->collectPlugins());
        $items = array_merge($items, $this->collectApps());

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['identifier'], (string) $b['identifier']));

        $remoteOk = true;
        $remoteMessage = '';
        $remoteMap = [];
        try {
            $remoteMap = $this->fetchRemoteLatestVersions(array_column($items, 'identifier'));
        } catch (Throwable $e) {
            $remoteOk = false;
            $remoteMessage = '无法读取云市场版本：' . $e->getMessage();
        }

        $marketIds = [];
        try {
            $marketIds = $this->fetchMarketIdentifiers(array_column($items, 'identifier'));
        } catch (Throwable) {
            // ignore; origin 回退为 local
        }

        $out = [];
        foreach ($items as $item) {
            $id = (string) $item['identifier'];
            $local = (string) ($item['version'] ?? '');
            $remote = (string) ($remoteMap[$id] ?? '');
            $inMarket = array_key_exists($id, $remoteMap) || isset($marketIds[$id]);
            $item['remote_version'] = $remote;
            $item['update_available'] = $remote !== '' && $this->isNewer($remote, $local);
            $item['in_market'] = $inMarket;
            // 云市场有登记 → 云应用；仅本机 apps/plugin → 本地应用
            $item['origin'] = $inMarket ? 'cloud' : 'local';
            $out[] = $item;
        }

        return [
            'list' => $out,
            'remote_ok' => $remoteOk,
            'remote_message' => $remoteMessage,
        ];
    }

    /**
     * @param list<string> $identifiers
     * @return array<string, true>
     */
    private function fetchMarketIdentifiers(array $identifiers): array
    {
        $identifiers = array_values(array_unique(array_filter(array_map('strval', $identifiers))));
        if ($identifiers === []) {
            return [];
        }
        $rows = Db::connection('platform')
            ->table('market_app')
            ->whereIn('identifier', $identifiers)
            ->pluck('identifier');
        $map = [];
        foreach ($rows as $id) {
            $map[(string) $id] = true;
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectPlugins(): array
    {
        $out = [];
        try {
            foreach (Plugin::getPluginJsonPaths() as $splFileInfo) {
                try {
                    $info = Plugin::read($splFileInfo->getRelativePath());
                } catch (Throwable) {
                    continue;
                }
                if ($info === [] || empty($info['name'])) {
                    continue;
                }
                $author = $info['author'] ?? '';
                if (is_array($author)) {
                    $names = [];
                    foreach ($author as $row) {
                        if (is_array($row) && isset($row['name'])) {
                            $names[] = (string) $row['name'];
                        } elseif (is_string($row)) {
                            $names[] = $row;
                        }
                    }
                    $author = implode(', ', $names);
                }
                $pluginId = (string) $info['name'];
                $out[] = [
                    'identifier' => $pluginId,
                    'title' => (string) ($info['description'] ?? $pluginId),
                    'version' => (string) ($info['version'] ?? ''),
                    'edition' => '',
                    'family' => $pluginId,
                    'description' => (string) ($info['description'] ?? ''),
                    'author' => (string) $author,
                    'type' => 'plugin',
                    'status' => (bool) ($info['status'] ?? false),
                ];
            }
        } catch (Throwable) {
            // plugin dir missing
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectApps(): array
    {
        $out = [];
        $root = AppPath::root();
        foreach (glob($root . '/*/*/app.json') ?: [] as $file) {
            if (! is_string($file)) {
                continue;
            }
            $dir = dirname($file);
            $app = basename($dir);
            $vendor = basename(dirname($dir));
            $identifier = $vendor . '/' . $app;
            try {
                $manifest = AppManifest::load($identifier);
            } catch (Throwable) {
                continue;
            }
            $out[] = [
                'identifier' => $identifier,
                'title' => (string) ($manifest['title'] ?? $manifest['name'] ?? $identifier),
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'edition' => AppEdition::editionFromManifest($manifest),
                'family' => AppEdition::familyFromManifest($manifest, $identifier),
                'description' => (string) ($manifest['description'] ?? $manifest['title'] ?? ''),
                'author' => $vendor,
                'type' => 'app',
                'status' => true,
            ];
        }

        return $out;
    }

    /**
     * @param list<string> $identifiers
     * @return array<string, string> identifier => latest_version
     */
    private function fetchRemoteLatestVersions(array $identifiers): array
    {
        $identifiers = array_values(array_unique(array_filter(array_map('strval', $identifiers))));
        if ($identifiers === []) {
            return [];
        }

        $apps = Db::connection('platform')
            ->table('market_app')
            ->whereIn('identifier', $identifiers)
            ->get(['id', 'identifier', 'status']);

        if ($apps->isEmpty()) {
            return [];
        }

        $idToIdent = [];
        foreach ($apps as $app) {
            $idToIdent[(int) $app->id] = (string) $app->identifier;
        }
        $appIds = array_keys($idToIdent);

        $versions = Db::connection('platform')
            ->table('market_app_version')
            ->whereIn('app_id', $appIds)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get(['app_id', 'version']);

        $map = [];
        foreach ($versions as $ver) {
            $ident = $idToIdent[(int) $ver->app_id] ?? '';
            if ($ident === '' || isset($map[$ident])) {
                continue;
            }
            $map[$ident] = (string) $ver->version;
        }

        // 无 active 版本时也标记在市场中（空版本）
        foreach ($idToIdent as $ident) {
            if (! array_key_exists($ident, $map)) {
                $map[$ident] = '';
            }
        }

        return $map;
    }

    private function isNewer(string $remote, string $local): bool
    {
        $remote = trim($remote);
        $local = trim($local);
        if ($remote === '' || $local === '') {
            return false;
        }
        if ($remote === $local) {
            return false;
        }
        // 优先语义化版本比较
        if (preg_match('/^\d+(\.\d+)*$/', $local) && preg_match('/^\d+(\.\d+)*$/', $remote)) {
            return version_compare($remote, $local, '>');
        }

        return version_compare($remote, $local, '>');
    }
}
