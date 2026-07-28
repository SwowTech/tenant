<?php

declare(strict_types=1);

namespace App\Http\Internal\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Library\App\AppGatewaySecret;
use App\Library\App\AppPath;
use App\Service\Storage\TenantFileStorageService;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * 应用上传文件到平台统一存储（本地或创始人配置的 OSS）.
 * 鉴权：X-App-Gateway-Secret（与网关反代一致）.
 */
#[Controller(prefix: '/internal/storage')]
final class StorageController extends AbstractController
{
    public function __construct(
        private readonly TenantFileStorageService $storage,
    ) {}

    #[PostMapping(path: 'upload')]
    public function upload(RequestInterface $request): Result
    {
        if (! $this->assertGatewaySecret($request)) {
            return $this->error('Invalid gateway secret');
        }

        $tenantId = (int) $request->input('tenant_id', $request->header('X-Tenant-Id') ?: 0);
        $identifier = (string) $request->input('identifier', $request->header('X-App-Identifier') ?: '');
        $category = (string) $request->input('category', 'misc');
        if ($identifier !== '') {
            try {
                $identifier = AppPath::assertSafeIdentifier($identifier);
            } catch (\Throwable) {
                return $this->error('invalid identifier');
            }
        }

        /** @var null|UploadedFile $file */
        $file = $request->file('file');
        if ($file === null) {
            return $this->error('缺少 file');
        }

        $tmp = sys_get_temp_dir() . '/' . uniqid('appup_', true);
        $ext = pathinfo((string) $file->getClientFilename(), PATHINFO_EXTENSION);
        if ($ext !== '') {
            $tmp .= '.' . $ext;
        }
        $file->moveTo($tmp);
        try {
            $stored = $this->storage->store(
                $tmp,
                (string) ($file->getClientFilename() ?: basename($tmp)),
                $tenantId,
                $identifier !== '' ? $identifier : 'platform',
                $category,
            );
        } finally {
            @unlink($tmp);
        }

        return $this->success($stored);
    }

    private function assertGatewaySecret(RequestInterface $request): bool
    {
        $got = $request->getHeaderLine('X-App-Gateway-Secret');
        if ($got === '') {
            $got = (string) $request->input('gateway_secret', '');
        }
        $expected = AppGatewaySecret::value();

        return $expected !== '' && hash_equals($expected, $got);
    }
}
