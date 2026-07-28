<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Library\Tenant\DynamicTablePrefix;
use App\Repository\SystemSettingRepository;

/**
 * 附件/OSS 配置：始终读写平台级 system_setting（无租户前缀），
 * 供创始人统一配置，全站应用共用。
 */
final class AttachmentSettingService
{
    public const KEY = 'attachment';

    public function __construct(
        private readonly SystemSettingRepository $repo,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    public function get(): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () {
            $cur = array_replace_recursive($this->defaults(), (array) $this->repo->get(self::KEY, []));
            $cur['php_env'] = [
                'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                'post_max_size' => (string) ini_get('post_max_size'),
            ];

            return $cur;
        });
    }

    public function save(array $data): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($data) {
            $cur = array_replace_recursive($this->defaults(), (array) $this->repo->get(self::KEY, []));

            if (array_key_exists('attachment_limit', $data)) {
                $cur['attachment_limit'] = max(0, (int) $data['attachment_limit']);
            }
            if (isset($data['image']) && is_array($data['image'])) {
                $cur['image'] = $this->mergeImage($cur['image'], $data['image']);
            }
            if (isset($data['audio']) && is_array($data['audio'])) {
                $cur['audio'] = $this->mergeAudio($cur['audio'], $data['audio']);
            }
            if (isset($data['remote']) && is_array($data['remote'])) {
                $cur['remote'] = $this->mergeRemote($cur['remote'], $data['remote']);
            }

            $this->repo->set(self::KEY, $cur);

            return $this->getInner();
        });
    }

    /** withoutPrefix 内调用，避免嵌套 withoutPrefix */
    private function getInner(): array
    {
        $cur = array_replace_recursive($this->defaults(), (array) $this->repo->get(self::KEY, []));
        $cur['php_env'] = [
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
        ];

        return $cur;
    }

    private function defaults(): array
    {
        return [
            'attachment_limit' => 0,
            'image' => [
                'thumb' => false,
                'width' => 800,
                'extentions' => [],
                'limit' => 0,
                'zip_percentage' => 100,
            ],
            'audio' => [
                'extentions' => [],
                'limit' => 0,
            ],
            'remote' => [
                'type' => 'off',
                'alioss' => [
                    'key' => '',
                    'secret' => '',
                    'bucket' => '',
                    'endpoint' => '',
                    'internal' => '0',
                    'url' => '',
                ],
            ],
        ];
    }

    private function mergeImage(array $cur, array $data): array
    {
        if (array_key_exists('thumb', $data)) {
            $cur['thumb'] = (bool) $data['thumb'];
        }
        if (array_key_exists('width', $data)) {
            $cur['width'] = max(0, (int) $data['width']);
        }
        if (array_key_exists('extentions', $data)) {
            $cur['extentions'] = $this->normalizeStringList($data['extentions']);
        }
        if (array_key_exists('limit', $data)) {
            $cur['limit'] = max(0, (int) $data['limit']);
        }
        if (array_key_exists('zip_percentage', $data)) {
            $pct = (int) $data['zip_percentage'];
            $cur['zip_percentage'] = ($pct <= 0 || $pct > 100) ? 100 : $pct;
        }

        return $cur;
    }

    private function mergeAudio(array $cur, array $data): array
    {
        if (array_key_exists('extentions', $data)) {
            $cur['extentions'] = $this->normalizeStringList($data['extentions']);
        }
        if (array_key_exists('limit', $data)) {
            $cur['limit'] = max(0, (int) $data['limit']);
        }

        return $cur;
    }

    private function mergeRemote(array $cur, array $data): array
    {
        if (array_key_exists('type', $data)) {
            $type = (string) $data['type'];
            $allowed = ['off', 'alioss'];
            if (in_array($type, $allowed, true)) {
                $cur['type'] = $type;
            }
        }
        foreach (['alioss'] as $provider) {
            if (isset($data[$provider]) && is_array($data[$provider])) {
                foreach ($data[$provider] as $k => $v) {
                    $cur[$provider][$k] = is_scalar($v) ? (string) $v : '';
                }
            }
        }

        return $cur;
    }

    /** @return list<string> */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $list = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $list[] = $item;
            }
        }

        return array_values(array_unique($list));
    }
}
