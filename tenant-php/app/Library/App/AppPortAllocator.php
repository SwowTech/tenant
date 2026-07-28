<?php

declare(strict_types=1);

namespace App\Library\App;

use RuntimeException;

/**
 * 应用进程端口分配：安装时按 29100–29999 顺序固定，避免运行时抢端口冲突.
 */
final class AppPortAllocator
{
    public const PORT_MIN = 29100;

    public const PORT_MAX = 29999;

    /**
     * 确保 identifier 已分配端口；已分配则返回原值.
     */
    public static function ensureAssigned(string $identifier): int
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $all = self::readAll();
        if (isset($all[$identifier]) && is_int($all[$identifier])) {
            return $all[$identifier];
        }
        if (isset($all[$identifier]) && is_numeric($all[$identifier])) {
            return (int) $all[$identifier];
        }

        $port = self::nextFreePort($all);
        $all[$identifier] = $port;
        self::writeAll($all);

        return $port;
    }

    public static function get(string $identifier): ?int
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $all = self::readAll();
        if (! isset($all[$identifier])) {
            return null;
        }

        return (int) $all[$identifier];
    }

    public static function listenFor(string $identifier): string
    {
        return '127.0.0.1:' . self::ensureAssigned($identifier);
    }

    public static function release(string $identifier): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $all = self::readAll();
        unset($all[$identifier]);
        self::writeAll($all);
    }

    /**
     * @param array<string, int|string> $all
     */
    private static function nextFreePort(array $all): int
    {
        $used = [];
        foreach ($all as $port) {
            $used[(int) $port] = true;
        }
        for ($port = self::PORT_MIN; $port <= self::PORT_MAX; ++$port) {
            if (! isset($used[$port])) {
                return $port;
            }
        }

        throw new RuntimeException('应用进程端口已用尽 (29100-29999)');
    }

    /**
     * @return array<string, int>
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
        $out = [];
        foreach ($decoded as $id => $port) {
            if (is_string($id) && is_numeric($port)) {
                $out[$id] = (int) $port;
            }
        }

        return $out;
    }

    /**
     * @param array<string, int> $all
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
            throw new RuntimeException('Unable to open app ports file');
        }
        try {
            if (! flock($fp, LOCK_EX)) {
                throw new RuntimeException('Unable to lock app ports file');
            }
            // 重读合并，避免并发覆盖
            rewind($fp);
            $existingRaw = stream_get_contents($fp);
            $existing = [];
            if (is_string($existingRaw) && trim($existingRaw) !== '') {
                $decoded = json_decode($existingRaw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $id => $port) {
                        if (is_string($id) && is_numeric($port)) {
                            $existing[$id] = (int) $port;
                        }
                    }
                }
            }
            $merged = $existing + $all;
            foreach ($all as $id => $port) {
                $merged[$id] = $port;
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
                $path = config('apps.ports');
                if (is_string($path) && $path !== '') {
                    return $path;
                }
            }
        } catch (\Throwable) {
        }

        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        return $base . '/runtime/apps/ports.json';
    }
}
