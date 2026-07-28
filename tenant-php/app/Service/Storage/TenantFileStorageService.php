<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Storage\TenantStoragePath;
use App\Library\Support\AppUrl;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Model\Attachment;
use App\Service\Setting\AttachmentSettingService;
use Hyperf\Filesystem\FilesystemFactory;
use OSS\OssClient;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Finder\SplFileInfo;

/**
 * 平台统一文件存储：创始人配置 OSS/本地，应用与后台共用；
 * 对象路径 tenant/{id}/app/{appKey}/...，便于后续 SaaS 下发同一套策略。
 */
final class TenantFileStorageService
{
    public function __construct(
        private readonly AttachmentSettingService $attachmentSettings,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly FilesystemFactory $filesystemFactory,
    ) {}

    /**
     * @return array{
     *   storage_mode: string,
     *   object_name: string,
     *   mime_type: string,
     *   storage_path: string,
     *   suffix: string,
     *   size_byte: int,
     *   size_info: string,
     *   url: string,
     *   relative_url: string
     * }
     */
    public function store(
        string $realPath,
        string $originName,
        ?int $tenantId = null,
        ?string $appIdentifier = null,
        string $category = '',
    ): array {
        if (! is_file($realPath)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '上传文件不存在');
        }

        $tenantId = $tenantId ?? TenantContext::id() ?? 0;
        $appIdentifier = $appIdentifier ?? TenantStoragePath::PLATFORM_APP;
        $cfg = $this->platformAttachmentConfig();
        $fileInfo = new SplFileInfo($realPath, dirname($realPath), basename($realPath));
        $this->assertLimits($fileInfo, $originName, $cfg);

        $remoteType = (string) ($cfg['remote']['type'] ?? 'off');
        if ($remoteType === 'alioss') {
            return $this->storeOss(
                $fileInfo,
                $originName,
                $cfg['remote']['alioss'] ?? [],
                $tenantId,
                $appIdentifier,
                $category,
            );
        }

        return $this->storeLocal($fileInfo, $originName, $tenantId, $appIdentifier, $category);
    }

    public function assertAllowed(string $realPath, string $originName): void
    {
        if (! is_file($realPath)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '上传文件不存在');
        }
        $cfg = $this->platformAttachmentConfig();
        $fileInfo = new SplFileInfo($realPath, dirname($realPath), basename($realPath));
        $this->assertLimits($fileInfo, $originName, $cfg);
    }

    /**
     * 平台级附件/OSS 配置（不读租户前缀表）.
     *
     * @return array<string, mixed>
     */
    public function platformAttachmentConfig(): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () {
            return $this->attachmentSettings->get();
        });
    }

    /**
     * 保存平台级附件配置（含 OSS），始终写入无前缀 system_setting.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function savePlatformAttachmentConfig(array $data): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($data) {
            return $this->attachmentSettings->save($data);
        });
    }

    /**
     * @param array<string, mixed> $cfg
     */
    private function assertLimits(SplFileInfo $fileInfo, string $originName, array $cfg): void
    {
        $size = (int) $fileInfo->getSize();
        $limitMb = (int) ($cfg['attachment_limit'] ?? 0);
        if ($limitMb > 0) {
            $limitBytes = $limitMb * 1024 * 1024;
            $used = (int) $this->dynamicTablePrefix->withoutPrefix(
                static fn () => (int) Attachment::query()->sum('size_byte')
            );
            if ($used + $size > $limitBytes) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '附件空间容量不足');
            }
        }

        $ext = strtolower(pathinfo($originName, PATHINFO_EXTENSION) ?: (string) $fileInfo->getExtension());
        $mime = (string) (@mime_content_type($fileInfo->getRealPath()) ?: '');
        $isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
        $isAudioVideo = str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/')
            || in_array($ext, ['mp3', 'mp4', 'wav', 'avi', 'mov', 'flv', 'wmv', 'm4a', 'aac'], true);

        if ($isImage) {
            $image = $cfg['image'] ?? [];
            $exts = $image['extentions'] ?? [];
            if (is_array($exts) && $exts !== [] && ! in_array($ext, array_map('strtolower', $exts), true)) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '不支持的图片后缀: ' . $ext);
            }
            $limitKb = (int) ($image['limit'] ?? 0);
            if ($limitKb > 0 && $size > $limitKb * 1024) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, "图片大小不能超过 {$limitKb}KB");
            }
        } elseif ($isAudioVideo) {
            $audio = $cfg['audio'] ?? [];
            $exts = $audio['extentions'] ?? [];
            if (is_array($exts) && $exts !== [] && ! in_array($ext, array_map('strtolower', $exts), true)) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '不支持的音视频后缀: ' . $ext);
            }
            $limitKb = (int) ($audio['limit'] ?? 0);
            if ($limitKb > 0 && $size > $limitKb * 1024) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, "音视频大小不能超过 {$limitKb}KB");
            }
        }
    }

    /**
     * @param array<string, mixed> $alioss
     * @return array{storage_mode: string, object_name: string, mime_type: string, storage_path: string, suffix: string, size_byte: int, size_info: string, url: string, relative_url: string}
     */
    private function storeOss(
        SplFileInfo $fileInfo,
        string $originName,
        array $alioss,
        int $tenantId,
        string $appIdentifier,
        string $category,
    ): array {
        $key = trim((string) ($alioss['key'] ?? ''));
        $secret = trim((string) ($alioss['secret'] ?? ''));
        $bucket = trim((string) ($alioss['bucket'] ?? ''));
        $endpoint = trim((string) ($alioss['endpoint'] ?? ''));
        $customUrl = rtrim(trim((string) ($alioss['url'] ?? '')), '/');
        $internal = ((string) ($alioss['internal'] ?? '0')) === '1';

        if ($key === '' || $secret === '' || $bucket === '' || $endpoint === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请完善阿里云 OSS 配置（含 Endpoint）');
        }

        if ($internal && ! str_contains($endpoint, '-internal')) {
            $endpoint = preg_replace('/(\.aliyuncs\.com)$/', '-internal$1', $endpoint) ?: $endpoint;
        }

        $suffix = strtolower(pathinfo($originName, PATHINFO_EXTENSION) ?: (string) $fileInfo->getExtension());
        $date = date('Y-m-d');
        $path = TenantStoragePath::relative($tenantId, $appIdentifier, $category, $date);
        $objectName = Uuid::uuid4()->toString() . ($suffix !== '' ? '.' . $suffix : '');
        $objectKey = $path . '/' . $objectName;
        $mime = (string) (@mime_content_type($fileInfo->getRealPath()) ?: 'application/octet-stream');
        $size = (int) $fileInfo->getSize();

        try {
            $client = new OssClient($key, $secret, $endpoint);
            $client->uploadFile($bucket, $objectKey, $fileInfo->getRealPath());
        } catch (\Throwable $e) {
            throw new BusinessException(ResultCode::FAIL, 'OSS 上传失败: ' . $e->getMessage());
        }

        if ($customUrl !== '') {
            $url = $customUrl . '/' . $objectKey;
        } else {
            $publicEndpoint = str_replace('-internal', '', $endpoint);
            $url = 'https://' . $bucket . '.' . $publicEndpoint . '/' . $objectKey;
        }

        return [
            'storage_mode' => 'oss',
            'object_name' => $objectName,
            'mime_type' => $mime,
            'storage_path' => $path,
            'suffix' => $suffix,
            'size_byte' => $size,
            'size_info' => (string) $size,
            'url' => $url,
            'relative_url' => '/' . ltrim($objectKey, '/'),
        ];
    }

    /**
     * @return array{storage_mode: string, object_name: string, mime_type: string, storage_path: string, suffix: string, size_byte: int, size_info: string, url: string, relative_url: string}
     */
    private function storeLocal(
        SplFileInfo $fileInfo,
        string $originName,
        int $tenantId,
        string $appIdentifier,
        string $category,
    ): array {
        $suffix = strtolower(pathinfo($originName, PATHINFO_EXTENSION) ?: (string) $fileInfo->getExtension());
        $date = date('Y-m-d');
        $path = TenantStoragePath::relative($tenantId, $appIdentifier, $category, $date);
        $objectName = Uuid::uuid4()->toString() . ($suffix !== '' ? '.' . $suffix : '');
        $mime = (string) (@mime_content_type($fileInfo->getRealPath()) ?: 'application/octet-stream');
        $size = (int) $fileInfo->getSize();
        $contents = (string) file_get_contents($fileInfo->getRealPath());

        $fs = $this->filesystemFactory->get('local');
        $fs->write($path . '/' . $objectName, $contents);

        // 同步一份到 public/uploads，兼容静态检查与部分部署
        $publicFile = BASE_PATH . '/public/uploads/' . $path . '/' . $objectName;
        $publicDir = dirname($publicFile);
        if (! is_dir($publicDir)) {
            @mkdir($publicDir, 0777, true);
        }
        @file_put_contents($publicFile, $contents);

        $relativeUrl = '/uploads/' . $path . '/' . $objectName;
        $url = rtrim(AppUrl::publicBase(), '/') . $relativeUrl;

        return [
            'storage_mode' => 'local',
            'object_name' => $objectName,
            'mime_type' => $mime,
            'storage_path' => $path,
            'suffix' => $suffix,
            'size_byte' => $size,
            'size_info' => (string) $size,
            'url' => $url,
            'relative_url' => $relativeUrl,
        ];
    }
}
