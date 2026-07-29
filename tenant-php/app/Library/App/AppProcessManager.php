<?php

declare(strict_types=1);

namespace App\Library\App;

use RuntimeException;

final class AppProcessManager
{
    /**
     * @return array<string, mixed>
     */
    public function ensureRunning(string $identifier): array
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $manifest = AppManifest::load($identifier);
        // 安装时分配的固定端口；未分配则在此补登（兼容旧应用）
        $listen = AppPortAllocator::listenFor($identifier);
        $existing = AppProcessRegistry::get($identifier);
        if ($existing !== null
            && (string) ($existing['listen'] ?? '') === $listen
            && $this->isAlive($existing)
            && $this->healthOk($listen)
        ) {
            return $existing;
        }

        $this->stopProcessOnly($identifier);
        $appDir = AppPath::appDir($identifier);
        $entryRel = (string) ($manifest['process']['entrypoint'] ?? 'bin/start.php');
        $entry = $appDir . '/' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $entryRel);
        $php = $this->phpBinary();
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $this->logFile($identifier), 'a'],
            2 => ['file', $this->logFile($identifier), 'a'],
        ];

        if (is_file($entry) && (str_ends_with($entry, 'start') || str_ends_with($entry, 'start.php') || str_ends_with($entry, 'start.bat'))) {
            $env = $this->buildEnv($listen, $identifier);
            if (str_ends_with(strtolower($entry), '.bat') && PHP_OS_FAMILY === 'Windows') {
                $cmd = 'cmd /c "' . $entry . '"';
            } else {
                $cmd = sprintf('"%s" "%s"', $php, $entry);
            }
            $proc = proc_open($cmd, $descriptors, $pipes, $appDir, $env);
        } else {
            $publicDir = $appDir . '/public';
            $router = is_file($entry) ? $entry : ($publicDir . '/index.php');
            if (! is_file($router)) {
                throw new RuntimeException("App entrypoint missing: {$router}");
            }
            $cmd = sprintf('"%s" -S %s -t "%s" "%s"', $php, $listen, $publicDir, $router);
            // 直启内置 server 时也必须注入宿主 DB_*（否则正式环境读不到 .env）
            $proc = proc_open($cmd, $descriptors, $pipes, $appDir, $this->buildEnv($listen, $identifier));
        }

        if (! is_resource($proc)) {
            throw new RuntimeException("Failed to start app process: {$identifier}");
        }
        $status = proc_get_status($proc);
        $pid = (int) ($status['pid'] ?? 0);
        $row = [
            'identifier' => $identifier,
            'version' => (string) ($manifest['version'] ?? ''),
            'listen' => $listen,
            'pid' => $pid,
            'status' => 'running',
        ];
        AppProcessRegistry::put($row);

        $deadline = microtime(true) + 8.0;
        while (microtime(true) < $deadline) {
            if ($this->healthOk($listen)) {
                return $row;
            }
            usleep(200_000);
        }

        throw new RuntimeException("App process health check failed: {$identifier} @ {$listen}");
    }

    /**
     * 安装/解压完成后分配固定端口（不启动进程）.
     */
    public function assignPort(string $identifier): int
    {
        return AppPortAllocator::ensureAssigned($identifier);
    }

    public function stop(string $identifier): void
    {
        $this->stopProcessOnly($identifier);
    }

    private function stopProcessOnly(string $identifier): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $existing = AppProcessRegistry::get($identifier);
        if ($existing === null) {
            return;
        }
        $pid = (int) ($existing['pid'] ?? 0);
        if ($pid > 0) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec('taskkill /F /T /PID ' . $pid . ' 2>NUL');
            } else {
                posix_kill($pid, SIGTERM);
            }
        }
        AppProcessRegistry::remove($identifier);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isAlive(array $row): bool
    {
        $pid = (int) ($row['pid'] ?? 0);
        if ($pid <= 0) {
            return false;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            $out = [];
            exec('tasklist /FI "PID eq ' . $pid . '" 2>NUL', $out);

            return count($out) > 1 && str_contains(implode("\n", $out), (string) $pid);
        }

        return posix_kill($pid, 0);
    }

    private function healthOk(string $listen): bool
    {
        if ($listen === '') {
            return false;
        }
        $url = 'http://' . $listen . '/health';
        $ctx = stream_context_create(['http' => ['timeout' => 1.0]]);
        $body = @file_get_contents($url, false, $ctx);
        if (! is_string($body)) {
            return false;
        }

        return str_contains($body, 'ok');
    }

    /**
     * @return array<string, string>
     */
    private function buildEnv(string $listen, string $identifier = ''): array
    {
        $env = [];
        foreach ($_ENV as $k => $v) {
            if (is_string($k) && (is_string($v) || is_numeric($v))) {
                $env[$k] = (string) $v;
            }
        }
        foreach ($_SERVER as $k => $v) {
            if (is_string($k) && is_string($v) && ! isset($env[$k])) {
                $env[$k] = $v;
            }
        }
        $env['APP_LISTEN'] = $listen;
        $env['APP_GATEWAY_SECRET'] = $this->gatewaySecret();
        $env['MINE_HOST_INTERNAL_URL'] = $this->hostInternalUrl();
        if ($identifier !== '') {
            $env['APP_SITE_PATH'] = '/' . str_replace('\\', '/', $identifier);
            $env['APP_IDENTIFIER'] = str_replace('\\', '/', $identifier);
        }
        foreach ($this->databaseEnv() as $k => $v) {
            $env[$k] = $v;
        }

        return $env;
    }

    /**
     * 把宿主 MySQL 凭据注入应用子进程（避免 CMS 安装配置里空密码导致 1045）.
     *
     * @return array<string, string>
     */
    private function databaseEnv(): array
    {
        $out = [];
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $db = config('databases.default');
                if (is_array($db)) {
                    $out['DB_HOST'] = (string) ($db['host'] ?? '127.0.0.1');
                    $out['DB_PORT'] = (string) ($db['port'] ?? 3306);
                    $out['DB_DATABASE'] = (string) ($db['database'] ?? 'mineadmin');
                    $out['DB_USERNAME'] = (string) ($db['username'] ?? 'root');
                    // 空密码也要写入，保证子进程 getenv('DB_PASSWORD') !== false
                    $out['DB_PASSWORD'] = (string) ($db['password'] ?? '');
                    $out['DB_CHARSET'] = (string) ($db['charset'] ?? 'utf8mb4');
                }
            }
        } catch (Throwable) {
        }

        // config 未就绪时：优先 Hyperf env()（读宿主 .env），再 getenv
        $keys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET'];
        foreach ($keys as $key) {
            if (isset($out[$key]) && ($key === 'DB_PASSWORD' || $out[$key] !== '')) {
                continue;
            }
            $v = null;
            try {
                if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                    $v = \Hyperf\Support\env($key);
                }
            } catch (Throwable) {
            }
            if ($v === null || $v === '') {
                $g = getenv($key);
                $v = $g === false ? null : $g;
            }
            if ($v !== null && ($key === 'DB_PASSWORD' || (string) $v !== '')) {
                $out[$key] = (string) $v;
            }
        }

        // 仍缺关键项时，直接解析宿主 .env 文件
        if (! isset($out['DB_HOST']) || $out['DB_HOST'] === '') {
            foreach ($this->readHostDotEnv() as $k => $v) {
                if (str_starts_with($k, 'DB_') && ! isset($out[$k])) {
                    $out[$k] = $v;
                }
            }
        }

        $yiqiDb = null;
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $yiqiDb = \Hyperf\Support\env('YIQICMS_DB_NAME');
            }
        } catch (Throwable) {
        }
        if (! is_string($yiqiDb) || $yiqiDb === '') {
            $g = getenv('YIQICMS_DB_NAME');
            $yiqiDb = $g === false ? null : $g;
        }
        if (is_string($yiqiDb) && $yiqiDb !== '') {
            $out['YIQICMS_DB_NAME'] = $yiqiDb;
        } elseif (! isset($out['YIQICMS_DB_NAME'])) {
            // 正式环境账号通常只有宿主库权限；勿默认 zzzcms（易 1044）
            $out['YIQICMS_DB_NAME'] = $out['DB_DATABASE'] ?? 'mineadmin';
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function readHostDotEnv(): array
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $envFile = $base . DIRECTORY_SEPARATOR . '.env';
        if (! is_file($envFile)) {
            return [];
        }
        $out = [];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if ($k !== '' && preg_match('/\A[A-Z][A-Z0-9_]*\z/', $k)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    private function hostInternalUrl(): string
    {
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $port = (int) (\Hyperf\Support\env('SERVER_PORT', 9501));
                return 'http://127.0.0.1:' . ($port > 0 ? $port : 9501);
            }
        } catch (Throwable) {
        }
        $port = (int) (getenv('SERVER_PORT') ?: 9501);

        return 'http://127.0.0.1:' . ($port > 0 ? $port : 9501);
    }

    private function gatewaySecret(): string
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

    private function phpBinary(): string
    {
        try {
            if (\Hyperf\Context\ApplicationContext::hasContainer()) {
                $bin = config('apps.php_binary');
                if (is_string($bin) && $bin !== '') {
                    return $bin;
                }
            }
        } catch (Throwable) {
        }

        return PHP_BINARY;
    }

    private function logFile(string $identifier): string
    {
        $safe = str_replace('/', '_', $identifier);
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $dir = $base . '/runtime/apps/logs';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/' . $safe . '.log';
    }
}
