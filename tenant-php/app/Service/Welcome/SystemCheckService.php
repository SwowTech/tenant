<?php

declare(strict_types=1);

namespace App\Service\Welcome;

use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;

use function Hyperf\Support\make;

final class SystemCheckService
{
    public function __construct(private readonly SaasBindService $saasBind) {}

    public function run(): array
    {
        $items = [];
        $items[] = $this->ext('mbstring');
        $items[] = $this->ext('openssl');
        $items[] = $this->ext('redis');
        $items[] = $this->ext('pdo_mysql');
        $items[] = $this->phpVersion();
        $items[] = $this->dirWritable(BASE_PATH . '/runtime', 'runtime');
        $items[] = $this->dirWritable(BASE_PATH . '/public/uploads', 'uploads');
        $items[] = $this->mysql();
        $items[] = $this->redis();
        $items[] = $this->saas();

        $wrong = 0;
        foreach ($items as $item) {
            if (! $item['ok']) {
                ++$wrong;
            }
        }

        return [
            'check_num' => count($items),
            'check_wrong_num' => $wrong,
            'items' => $items,
            'report_text' => $this->toReport($items, $wrong),
        ];
    }

    private function ext(string $ext): array
    {
        $ok = extension_loaded($ext);

        return $this->item(
            'ext_' . $ext,
            'ext',
            $ok,
            $ok ? '' : 'install_ext',
            $ok ? '' : 'install_ext',
            'https://www.php.net/' . $ext,
            ['ext' => $ext],
        );
    }

    private function phpVersion(): array
    {
        $ok = version_compare(PHP_VERSION, '8.1.0', '>=');

        return $this->item(
            'php_version',
            'php_version',
            $ok,
            $ok ? '' : 'upgrade_php',
            $ok ? '' : 'upgrade_php',
            '',
        );
    }

    private function dirWritable(string $path, string $label): array
    {
        if (! is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        $ok = is_dir($path) && is_writable($path);

        return $this->item(
            'dir_' . $label,
            'dir_writable',
            $ok,
            $ok ? '' : 'chmod_dir',
            $ok ? '' : 'chmod_dir',
            '',
            ['dir' => $label, 'path' => $path],
        );
    }

    private function mysql(): array
    {
        try {
            Db::select('select 1');

            return $this->item('mysql', 'mysql', true, '', '', '');
        } catch (\Throwable $e) {
            return $this->item('mysql', 'mysql', false, 'check_db', 'check_db', '', [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    private function redis(): array
    {
        try {
            $redis = make(Redis::class);
            $redis->ping();

            return $this->item('redis_ping', 'redis', true, '', '', '');
        } catch (\Throwable $e) {
            return $this->item('redis_ping', 'redis', false, 'check_redis', 'check_redis', '', [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    private function saas(): array
    {
        $bind = $this->saasBind->status();
        $bound = (bool) $bind['bound'];

        return $this->item(
            'saas_bind',
            'saas_bind',
            true,
            $bound ? '' : 'saas_optional',
            $bound ? '' : 'go_register',
            $bound ? '' : (string) $bind['bind_url'],
        );
    }

    /**
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function item(
        string $key,
        string $name,
        bool $ok,
        string $suggestion,
        string $action,
        string $solution,
        array $params = [],
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'ok' => $ok,
            'suggestion' => $suggestion,
            'action' => $action,
            'solution' => $solution,
            'params' => $params,
        ];
    }

    private function toReport(array $items, int $wrong): string
    {
        $lines = [sprintf('check_total=%d wrong=%d', count($items), $wrong), ''];
        foreach ($items as $item) {
            $lines[] = sprintf(
                '%s: %s%s',
                $item['key'],
                $item['ok'] ? 'ok' : 'fail',
                $item['ok'] ? '' : (' | ' . $item['suggestion'])
            );
        }

        return implode("\n", $lines);
    }
}
