<?php

declare(strict_types=1);

namespace App\Library\App;

final class AppGatewaySecret
{
    public static function value(): string
    {
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $secret = config('apps.gateway_secret');
                if (is_string($secret) && $secret !== '') {
                    return $secret;
                }
            }
        } catch (Throwable) {
        }

        return (string) (getenv('APP_GATEWAY_SECRET') ?: 'dev-app-gateway-secret');
    }
}
