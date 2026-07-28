<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Service;

use App\Model\Attachment;
use App\Repository\AttachmentRepository;
use App\Service\Setting\AttachmentUploadGateway;
use Mine\Upload\UploadInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @extends IService<Attachment>
 */
final class AttachmentService extends IService
{
    public function __construct(
        protected readonly AttachmentRepository $repository,
        protected readonly UploadInterface $upload,
        protected readonly AttachmentUploadGateway $gateway,
    ) {}

    public function upload(SplFileInfo $fileInfo, UploadedFileInterface $uploadedFile, int $userId): Attachment
    {
        $fileHash = md5_file($fileInfo->getRealPath());
        if ($attachment = $this->repository->findByHash($fileHash)) {
            return $attachment;
        }

        $originName = (string) $uploadedFile->getClientFilename();
        $remote = $this->gateway->tryRemoteStore($fileInfo, $originName);
        if ($remote !== null) {
            return $this->repository->create([
                'created_by' => $userId,
                'origin_name' => $originName,
                'storage_mode' => $remote['storage_mode'],
                'object_name' => $remote['object_name'],
                'mime_type' => $remote['mime_type'],
                'storage_path' => $remote['storage_path'],
                'hash' => $fileHash,
                'suffix' => $remote['suffix'],
                'size_byte' => $remote['size_byte'],
                'size_info' => $remote['size_info'],
                'url' => $remote['url'],
            ]);
        }

        // Local: still enforce limits, then Mine Upload subscriber
        $this->gateway->assertLocalAllowed($fileInfo, $originName);
        $upload = $this->upload->upload($fileInfo);

        return $this->repository->create([
            'created_by' => $userId,
            'origin_name' => $originName,
            'storage_mode' => $upload->getStorageMode(),
            'object_name' => $upload->getObjectName(),
            'mime_type' => $upload->getMimeType(),
            'storage_path' => $upload->getStoragePath(),
            'hash' => $fileHash,
            'suffix' => $upload->getSuffix(),
            'size_byte' => $upload->getSizeByte(),
            'size_info' => $upload->getSizeInfo(),
            'url' => $upload->getUrl(),
        ]);
    }

    public function getRepository(): AttachmentRepository
    {
        return $this->repository;
    }
}
