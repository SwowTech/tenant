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

namespace App\Http\Common\Swagger;

use Hyperf\Swagger\Annotation as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        version: '1.0.0',
        description: 'swow.tech 是基于 Hyperf 的开源租户站点框架（基于 MineAdmin Apache-2.0），提供用户管理、权限管理、系统设置、系统监控等功能。',
        title: 'swow.tech',
        termsOfService: 'https://swow.tech',
        contact: new OA\Contact(name: 'swow.tech', url: 'https://swow.tech'),
        license: new OA\License(name: 'Apache2.0', url: 'https://github.com/mineadmin/MineAdmin/blob/master/LICENSE')
    ),
    servers: [
        new OA\Server(
            url: 'http://127.0.0.1:9501',
            description: '本地服务'
        ),
        new OA\Server(
            url: 'https://demo.mineadmin.com',
            description: '演示服务',
        ),
    ],
    externalDocs: new OA\ExternalDocumentation(description: '开发文档', url: 'https://swow.tech')
)]
#[OA\SecurityScheme(
    securityScheme: 'Bearer',
    type: 'http',
    name: 'Authorization',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\SecurityScheme(
    securityScheme: 'ApiKey',
    type: 'apiKey',
    name: 'token',
    in: 'header'
)]
final class Server {}
