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
use App\Http\Middleware\AppGatewayMiddleware;
use App\Http\Middleware\ServeHostUploadsMiddleware;
use App\Http\Middleware\SiteClosedMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use Mine\Support\Middleware\CorsMiddleware;
use Mine\Support\Middleware\RequestIdMiddleware;
use Mine\Support\Middleware\TranslationMiddleware;

return [
    'http' => [
        RequestIdMiddleware::class,
        TranslationMiddleware::class,
        CorsMiddleware::class,
        ValidationMiddleware::class,
        TenantMiddleware::class,
        SiteClosedMiddleware::class,
        ServeHostUploadsMiddleware::class,
        AppGatewayMiddleware::class,
    ],
];
