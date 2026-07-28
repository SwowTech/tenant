<?php

declare(strict_types=1);

namespace App\Service\Setting;

use Hyperf\DbConnection\Db;

use function Hyperf\Support\env;

final class SystemInfoService
{
    public function collect(): array
    {
        return [
            'app_version' => (string) env('APP_VERSION', '1.0.0'),
            'family' => 'swow.tech 租户管理系统',
            'os' => $this->os(),
            'php' => PHP_VERSION,
            'sapi' => $this->sapi(),
            'mysql_version' => $this->mysqlVersion(),
            'upload_max' => (string) ini_get('upload_max_filesize'),
            'db_size' => $this->dbSize(),
            'attach_url' => \App\Library\Support\AppUrl::publicBase() . '/uploads',
            'attach_size' => '点击查看',
            'copyright' => [
                'name' => 'swow.tech',
                'url' => 'https://swow.tech',
            ],
        ];
    }

    private function os(): string
    {
        return PHP_OS_FAMILY . ' / ' . php_uname();
    }

    private function sapi(): string
    {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? '';

        return $software !== ''
            ? php_sapi_name() . ' / ' . $software
            : php_sapi_name();
    }

    private function mysqlVersion(): string
    {
        try {
            $rows = Db::select('select version() as v');

            return (string) ($rows[0]->v ?? 'unknown');
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    private function dbSize(): string
    {
        try {
            $database = (string) config('databases.default.database');
            $rows = Db::select(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                 FROM information_schema.tables WHERE table_schema = ?',
                [$database]
            );
            $mb = $rows[0]->size_mb ?? null;
            if ($mb === null) {
                return 'unknown';
            }

            return $mb . ' MB';
        } catch (\Throwable) {
            return 'unknown';
        }
    }
}
