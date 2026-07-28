<?php

declare(strict_types=1);

namespace App\Library\App;

use RuntimeException;

final class AppProcessRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::readAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $identifier): ?array
    {
        $all = self::readAll();

        return $all[$identifier] ?? null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function put(array $row): void
    {
        $identifier = (string) ($row['identifier'] ?? '');
        if ($identifier === '') {
            throw new RuntimeException('registry row missing identifier');
        }
        $all = self::readAll();
        $row['updated_at'] = date('c');
        $all[$identifier] = $row;
        self::writeAll($all);
    }

    public static function remove(string $identifier): void
    {
        $all = self::readAll();
        unset($all[$identifier]);
        self::writeAll($all);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function readAll(): array
    {
        $file = self::filePath();
        if (! is_file($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param array<string, array<string, mixed>> $all
     */
    private static function writeAll(array $all): void
    {
        $file = self::filePath();
        $dir = dirname($file);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            throw new RuntimeException('Unable to open app registry');
        }
        try {
            if (! flock($fp, LOCK_EX)) {
                throw new RuntimeException('Unable to lock app registry');
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private static function filePath(): string
    {
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $path = config('apps.registry');
                if (is_string($path) && $path !== '') {
                    return $path;
                }
            }
        } catch (Throwable) {
        }

        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        return $base . '/runtime/apps/registry.json';
    }
}
