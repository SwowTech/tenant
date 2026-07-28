<?php

declare(strict_types=1);

/**
 * Install mineadmin/hyperf-crontab on Swow + Windows.
 * - Path must use backslashes (Finder getRelativePath on Windows)
 * - Polyfill Swoole\Coroutine\System::exec (app-store Plugin hard-depends on it)
 *
 * Usage: php scripts/install-hyperf-crontab-swow.php
 */

namespace Swoole\Coroutine {
    if (! class_exists(System::class, false)) {
        final class System
        {
            /**
             * @return array{code: int, output: string}
             */
            public static function exec(string $command, float $timeout = -1): array
            {
                $output = [];
                $code = 0;
                \exec($command . ' 2>&1', $output, $code);

                return [
                    'code' => $code,
                    'output' => implode("\n", $output),
                ];
            }
        }
    }
}

namespace {
    ! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

    require BASE_PATH . '/vendor/autoload.php';
    require BASE_PATH . '/tests/bootstrap.php';

    use Mine\AppStore\Plugin;

    $path = 'mineadmin\\hyperf-crontab';
    Plugin::forceRefreshJsonPath();
    $info = Plugin::read($path);
    if (! empty($info['status'])) {
        echo "ALREADY_INSTALLED\n";
        exit(0);
    }

    Plugin::install($path);
    echo "INSTALLED_OK\n";
}
