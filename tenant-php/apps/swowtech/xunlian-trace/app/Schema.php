<?php

declare(strict_types=1);

namespace TraceApp;

final class Schema
{
    /** @var array<string, true> */
    private static array $ready = [];

    public static function ensure(): void
    {
        $prefix = Tenant::prefix();
        if (isset(self::$ready[$prefix])) {
            return;
        }
        $pdo = Db::pdo();
        $product = Tenant::table('trace_product');
        $check = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($product));
        if ($check && $check->fetch()) {
            self::$ready[$prefix] = true;

            return;
        }
        $file = dirname(__DIR__) . '/database/schema.sql';
        $sql = str_replace('__PREFIX__', $prefix, (string) file_get_contents($file));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '') {
                continue;
            }
            // 去掉块首注释行，避免整段 CREATE 被跳过
            $lines = preg_split("/\r\n|\n|\r/", $stmt) ?: [];
            $kept = [];
            foreach ($lines as $line) {
                $trim = ltrim($line);
                if ($trim === '' || str_starts_with($trim, '--')) {
                    continue;
                }
                $kept[] = $line;
            }
            $stmt = trim(implode("\n", $kept));
            if ($stmt === '') {
                continue;
            }
            $pdo->exec($stmt);
        }
        self::$ready[$prefix] = true;
    }
}
