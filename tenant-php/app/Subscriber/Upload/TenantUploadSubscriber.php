<?php

declare(strict_types=1);

namespace App\Subscriber\Upload;

use App\Library\Storage\TenantStoragePath;
use Mine\Upload\Listener\UploadListener as AbstractUploadListener;

/**
 * 本地附件按租户分目录：tenant/{id}/app/platform/{Y-m-d}/
 */
final class TenantUploadSubscriber extends AbstractUploadListener
{
    public const ADAPTER_NAME = 'local';

    protected function generatorPath(): string
    {
        return TenantStoragePath::relative(null, null, '', date('Y-m-d'));
    }
}
