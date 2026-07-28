<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Library\Storage\TenantStoragePath;
use App\Library\Tenant\TenantContext;
use App\Service\Storage\TenantFileStorageService;
use Symfony\Component\Finder\SplFileInfo;

/**
 * 后台附件上传网关：OSS 开启时走统一存储；否则仅校验后交 Mine Upload（已按租户分目录）.
 */
final class AttachmentUploadGateway
{
    public function __construct(
        private readonly TenantFileStorageService $storage,
    ) {}

    /**
     * @return null|array{storage_mode:string,object_name:string,mime_type:string,storage_path:string,suffix:string,size_byte:int,size_info:string,url:string}
     */
    public function tryRemoteStore(SplFileInfo $fileInfo, string $originName): ?array
    {
        $cfg = $this->storage->platformAttachmentConfig();
        if ((string) ($cfg['remote']['type'] ?? 'off') !== 'alioss') {
            return null;
        }

        $stored = $this->storage->store(
            $fileInfo->getRealPath(),
            $originName,
            TenantContext::id() ?? 0,
            TenantStoragePath::PLATFORM_APP,
            '',
        );
        @unlink($fileInfo->getRealPath());

        return [
            'storage_mode' => $stored['storage_mode'],
            'object_name' => $stored['object_name'],
            'mime_type' => $stored['mime_type'],
            'storage_path' => $stored['storage_path'],
            'suffix' => $stored['suffix'],
            'size_byte' => $stored['size_byte'],
            'size_info' => $stored['size_info'],
            'url' => $stored['url'],
        ];
    }

    public function assertLocalAllowed(SplFileInfo $fileInfo, string $originName): void
    {
        $this->storage->assertAllowed($fileInfo->getRealPath(), $originName);
    }
}
