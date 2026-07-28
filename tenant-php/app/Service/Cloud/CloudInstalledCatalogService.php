<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use App\Library\App\AppEdition;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Cloud\SaasPublicClient;
use Mine\AppStore\Plugin;
use Throwable;

/**
 * 应用管理页：本地插件/应用清单 + 云市场来源与版本对比（经 SaaS HTTP API）.
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
        $marketIds = [];
        try {
            $payload = $this->fetchRemoteCatalog(array_column($items, 'identifier'));
            $remoteMap = $payload['versions'];
            $marketIds = $payload['exists'];
        } catch (Throwable $e) {
            $remoteOk = false;
            $remoteMessage = '无法读取云市场版本：' . $e->getMessage();
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
     * @return array{versions: array<string, string>, exists: array<string, true>}
     */
    private function fetchRemoteCatalog(array $identifiers): array
    {
        $identifiers = array_values(array_unique(array_filter(array_map('strval', $identifiers))));
        if ($identifiers === []) {
            return ['versions' => [], 'exists' => []];
        }

        $data = SaasPublicClient::get('/store/apps/versions', [
            'identifiers' => implode(',', $identifiers),
        ]);

        $versions = [];
        foreach (($data['versions'] ?? []) as $k => $v) {
            $versions[(string) $k] = (string) $v;
        }
        $exists = [];
        foreach (($data['exists'] ?? []) as $k => $v) {
            if ($v) {
                $exists[(string) $k] = true;
            }
        }
        foreach ($versions as $k => $_) {
            $exists[$k] = true;
        }

        return ['versions' => $versions, 'exists' => $exists];
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
        if (preg_match('/^\d+(\.\d+)*$/', $local) && preg_match('/^\d+(\.\d+)*$/', $remote)) {
            return version_compare($remote, $local, '>');
        }

        return version_compare($remote, $local, '>');
    }
}
