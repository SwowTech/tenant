<?php

declare(strict_types=1);

namespace App\Library\App;

use InvalidArgumentException;

final class AppPath
{
    private const IDENTIFIER_PATTERN = '/\A[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+\z/';

    public static function root(): string
    {
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $root = config('apps.root');
                if (is_string($root) && $root !== '') {
                    return $root;
                }
            }
        } catch (Throwable) {
            // CLI scripts without Hyperf bootstrap
        }

        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        return $base . '/apps';
    }

    public static function assertSafeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '' || preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw new InvalidArgumentException('Invalid app identifier');
        }

        return $identifier;
    }

    public static function appDir(string $identifier): string
    {
        $identifier = self::assertSafeIdentifier($identifier);
        [$vendor, $app] = explode('/', $identifier, 2);

        return self::root() . '/' . $vendor . '/' . $app;
    }
}
