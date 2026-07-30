<?php

declare(strict_types=1);

/**
 * 应用配置（独立进程内读取；不覆盖宿主）.
 */
return [
    'name' => 'mineadmin/demo',
    'auth' => [
        'issuer' => 'app-member:mineadmin/demo',
    ],
];
