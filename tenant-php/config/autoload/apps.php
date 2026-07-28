<?php

declare(strict_types=1);

return [
    'root' => BASE_PATH . '/apps',
    'registry' => BASE_PATH . '/runtime/apps/registry.json',
    'ports' => BASE_PATH . '/runtime/apps/ports.json',
    'gateway_secret' => env('APP_GATEWAY_SECRET', 'dev-app-gateway-secret'),
    'php_binary' => env('APP_PHP_BINARY', PHP_BINARY),
];
