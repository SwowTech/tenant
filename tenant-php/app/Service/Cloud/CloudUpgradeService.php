<?php

declare(strict_types=1);

namespace App\Service\Cloud;

use App\Library\Support\InProcessMigrate;
use App\Repository\SystemSettingRepository;
use GuzzleHttp\Client;
use Hyperf\Coroutine\Coroutine;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use function Hyperf\Support\env;

final class CloudUpgradeService
{
    public const VERSION_KEY = 'cloud.system.version';

    public function __construct(
        private readonly CloudUpgradeTaskStore $store,
        private readonly SystemSettingRepository $settings,
        private readonly ContainerInterface $container,
    ) {}

    public function currentVersion(): string
    {
        $raw = $this->settings->get(self::VERSION_KEY);
        if (is_array($raw)) {
            $v = trim((string) ($raw['version'] ?? $raw['value'] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
        if (is_string($raw)) {
            $v = trim($raw);
            if ($v !== '') {
                return $v;
            }
        }

        return (string) env('APP_VERSION', '1.0.0');
    }

    public function writeVersion(string $version): void
    {
        $version = trim($version);
        if ($version === '') {
            throw new \InvalidArgumentException('版本号不能为空');
        }
        $this->settings->set(self::VERSION_KEY, ['version' => $version]);
    }

    /**
     * @return array{current_version: string, latest_task: array<string, mixed>|null}
     */
    public function overview(): array
    {
        return [
            'current_version' => $this->currentVersion(),
            'latest_task' => $this->store->latest(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $base = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        if ($base === '') {
            throw new \RuntimeException('未配置 SAAS_PHP_PUBLIC_URL，无法检查升级');
        }
        $url = $base . '/cloud/upgrade/check?current=' . rawurlencode($this->currentVersion());
        $body = $this->saasGetJson($url);
        $code = (int) ($body['code'] ?? 0);
        if ($code !== 200) {
            $msg = (string) ($body['message'] ?? 'saas 检查升级失败');
            throw new \RuntimeException($msg);
        }
        $data = $body['data'] ?? null;
        if (! is_array($data)) {
            throw new \RuntimeException('saas 检查升级返回无效');
        }

        return $data;
    }

    /**
     * Treat agreed as true only for bool true, int 1, or string "true"/"1".
     */
    public static function parseAgreed(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        if ($value === false || $value === 0 || $value === null) {
            return false;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return false;
            }

            return in_array(strtolower($trimmed), ['true', '1'], true);
        }

        return false;
    }

    /**
     * @return array{task_id: string, poll_interval: int}
     */
    public function start(mixed $agreed): array
    {
        if (! self::parseAgreed($agreed)) {
            throw new \InvalidArgumentException('请先勾选全部更新协议');
        }
        $info = $this->check();
        if (empty($info['upgrade'])) {
            throw new \RuntimeException((string) ($info['message'] ?? '没有可升级版本'));
        }
        $task = $this->store->createIfNotRunning([
            'target_version' => (string) ($info['version'] ?? ''),
            'status' => 'pending',
            'step' => 'pending',
            'progress' => 0,
        ]);
        Coroutine::create(fn () => $this->run((string) $task['id'], $info));

        return ['task_id' => (string) $task['id'], 'poll_interval' => 1000];
    }

    /**
     * @return array<string, mixed>
     */
    public function task(string $id): array
    {
        $task = $this->store->get($id);
        if ($task === null) {
            throw new \RuntimeException('升级任务不存在');
        }

        return $task;
    }

    /**
     * Overlay / frontend 路径拼接，拒绝逃逸。
     */
    public function safeJoin(string $root, string $rel): string
    {
        $rel = str_replace('\\', '/', $rel);
        if ($rel === '' || str_starts_with($rel, '/') || str_contains($rel, '..')) {
            throw new \RuntimeException('非法 overlay 路径: ' . $rel);
        }
        $rootNorm = rtrim(str_replace('\\', '/', $root), '/');
        $full = $rootNorm . '/' . ltrim($rel, '/');
        $realRoot = realpath($rootNorm) ?: $rootNorm;
        $realRoot = str_replace('\\', '/', $realRoot);
        $parent = dirname($full);
        if (! is_dir($parent) && ! mkdir($parent, 0775, true) && ! is_dir($parent)) {
            throw new \RuntimeException('无法创建目录: ' . $parent);
        }
        $realParent = realpath($parent) ?: $parent;
        $realParent = str_replace('\\', '/', $realParent);
        $prefix = rtrim($realRoot, '/') . '/';
        if ($realParent !== $realRoot && ! str_starts_with($realParent . '/', $prefix)) {
            throw new \RuntimeException('非法 overlay 路径: ' . $rel);
        }

        return $full;
    }

    /**
     * @param array<string, mixed> $info
     */
    public function run(string $taskId, array $info): void
    {
        $workDir = null;
        try {
            $this->progress($taskId, 'confirm', 5, '确认目标版本');
            $confirm = $this->check();
            $target = (string) ($info['version'] ?? '');
            if (empty($confirm['upgrade']) || (string) ($confirm['version'] ?? '') !== $target) {
                throw new \RuntimeException('目标版本已不可用，请重新检查更新');
            }
            $sha256 = (string) ($confirm['sha256'] ?? $info['sha256'] ?? '');

            $this->progress($taskId, 'download', 15, '下载升级包');
            $zipPath = $this->downloadPackage($target);
            $workDir = dirname($zipPath);

            $this->progress($taskId, 'verify', 30, '校验升级包');
            $this->verifyPackage($zipPath, $sha256);
            $extractDir = $workDir . '/extract';
            $this->extractZip($zipPath, $extractDir);
            $manifest = $this->readManifest($extractDir);
            if ((string) ($manifest['version'] ?? '') !== '' && (string) $manifest['version'] !== $target) {
                throw new \RuntimeException('manifest 版本与目标版本不一致');
            }

            $this->progress($taskId, 'migrate', 45, '执行数据库迁移');
            $this->applyMigrations($extractDir);

            $this->progress($taskId, 'scripts', 60, '执行升级脚本');
            $this->applyScripts($extractDir);

            $this->progress($taskId, 'overlay', 75, '覆盖后端文件');
            $this->applyOverlay($extractDir);

            $this->progress($taskId, 'frontend', 90, '同步前端静态资源');
            $this->applyFrontend($extractDir, $taskId);

            $this->progress($taskId, 'version', 95, '写入版本号');
            $this->writeVersion($target);

            $this->store->update($taskId, [
                'status' => 'success',
                'step' => 'done',
                'progress' => 100,
                'message' => '升级成功',
                'append_log' => '升级完成: ' . $target,
            ]);
        } catch (\Throwable $e) {
            $this->store->update($taskId, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'append_log' => '失败: ' . $e->getMessage(),
            ]);
        } finally {
            $this->store->clearRunningLock();
            if (is_string($workDir) && is_dir($workDir)) {
                $this->removeDir($workDir);
            }
        }
    }

    private function progress(string $taskId, string $step, int $progress, string $message): void
    {
        $this->store->update($taskId, [
            'status' => 'running',
            'step' => $step,
            'progress' => $progress,
            'message' => $message,
            'append_log' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function saasGetJson(string $url): array
    {
        $headers = ['Accept' => 'application/json'];
        $token = trim((string) env('CLOUD_UPGRADE_PUBLIC_TOKEN', ''));
        if ($token !== '') {
            $headers['X-Upgrade-Token'] = $token;
        }
        try {
            $client = new Client(['timeout' => 30, 'http_errors' => false, 'allow_redirects' => true]);
            $resp = $client->get($url, ['headers' => $headers]);
            $body = json_decode((string) $resp->getBody(), true);
            if (! is_array($body)) {
                throw new \RuntimeException('saas 响应无效');
            }

            return $body;
        } catch (\Throwable $e) {
            throw new \RuntimeException('连接 saas 失败: ' . $e->getMessage(), 0, $e);
        }
    }

    private function downloadPackage(string $version): string
    {
        $base = rtrim((string) env('SAAS_PHP_PUBLIC_URL', 'http://127.0.0.1:9502'), '/');
        $url = $base . '/cloud/upgrade/package/' . rawurlencode($version);
        $headers = ['Accept' => 'application/zip, application/octet-stream, */*'];
        $token = trim((string) env('CLOUD_UPGRADE_PUBLIC_TOKEN', ''));
        if ($token !== '') {
            $headers['X-Upgrade-Token'] = $token;
        }
        $maxMb = (int) env('CLOUD_UPGRADE_MAX_PACKAGE_MB', 200);
        if ($maxMb < 1) {
            $maxMb = 200;
        }

        $workDir = BASE_PATH . '/runtime/cloud-upgrade/work-' . bin2hex(random_bytes(6));
        if (! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
            throw new \RuntimeException('无法创建临时目录');
        }
        $zipPath = $workDir . '/release.zip';

        try {
            $client = new Client([
                'timeout' => 300,
                'http_errors' => false,
                'allow_redirects' => true,
                'sink' => $zipPath,
            ]);
            $resp = $client->get($url, ['headers' => $headers]);
            $status = $resp->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $snippet = '';
                if (is_file($zipPath)) {
                    $snippet = substr((string) file_get_contents($zipPath), 0, 200);
                    @unlink($zipPath);
                }
                throw new \RuntimeException('下载升级包失败 HTTP ' . $status . ($snippet !== '' ? ': ' . $snippet : ''));
            }
        } catch (\RuntimeException $e) {
            $this->removeDir($workDir);
            throw $e;
        } catch (\Throwable $e) {
            $this->removeDir($workDir);
            throw new \RuntimeException('下载升级包失败: ' . $e->getMessage(), 0, $e);
        }

        if (! is_file($zipPath)) {
            $this->removeDir($workDir);
            throw new \RuntimeException('升级包未保存成功');
        }
        $size = filesize($zipPath) ?: 0;
        if ($size > $maxMb * 1024 * 1024) {
            $this->removeDir($workDir);
            throw new \RuntimeException("升级包超过 {$maxMb}MB");
        }
        if ($size < 32) {
            $this->removeDir($workDir);
            throw new \RuntimeException('升级包过小或无效');
        }

        return $zipPath;
    }

    private function verifyPackage(string $zipPath, string $expectedSha256): void
    {
        if ($expectedSha256 === '') {
            throw new \RuntimeException('缺少 sha256 校验信息');
        }
        $actual = hash_file('sha256', $zipPath);
        if (! is_string($actual) || ! hash_equals(strtolower($expectedSha256), strtolower($actual))) {
            throw new \RuntimeException('升级包 sha256 校验失败');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('无法打开升级包 zip');
        }
        $hasManifest = $zip->locateName('manifest.json') !== false;
        $zip->close();
        if (! $hasManifest) {
            throw new \RuntimeException('升级包缺少 manifest.json');
        }
    }

    private function extractZip(string $zipPath, string $dest): void
    {
        if (! is_dir($dest) && ! mkdir($dest, 0775, true) && ! is_dir($dest)) {
            throw new \RuntimeException('无法创建解压目录');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('无法打开升级包 zip');
        }
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || $name === '') {
                continue;
            }
            $norm = str_replace('\\', '/', $name);
            if (str_starts_with($norm, '/') || str_contains($norm, '..')) {
                $zip->close();
                throw new \RuntimeException('升级包含非法路径: ' . $name);
            }
        }
        if (! $zip->extractTo($dest)) {
            $zip->close();
            throw new \RuntimeException('解压升级包失败');
        }
        $zip->close();
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $extractDir): array
    {
        $path = $extractDir . '/manifest.json';
        if (! is_file($path)) {
            throw new \RuntimeException('解压后缺少 manifest.json');
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            throw new \RuntimeException('manifest.json 无效');
        }

        return $data;
    }

    private function applyMigrations(string $extractDir): void
    {
        $src = $extractDir . '/backend/migrations';
        if (! is_dir($src)) {
            return;
        }
        $dest = BASE_PATH . '/databases/migrations';
        if (! is_dir($dest) && ! mkdir($dest, 0775, true) && ! is_dir($dest)) {
            throw new \RuntimeException('无法创建 migrations 目录');
        }
        $files = glob($src . '/*.php') ?: [];
        if ($files === []) {
            return;
        }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $base = basename($file);
            $target = $dest . '/' . $base;
            if (is_file($target)) {
                $target = $dest . '/cloud_upg_' . $base;
            }
            if (! copy($file, $target)) {
                throw new \RuntimeException('复制迁移文件失败: ' . $base);
            }
        }
        $this->runMigrate();
    }

    /**
     * Prefer in-process migrate (without WORKER_EXIT);
     * fall back to `bin/hyperf.php migrate` via PHP_BINARY if needed.
     */
    private function runMigrate(): void
    {
        try {
            $output = new BufferedOutput();
            $code = InProcessMigrate::run($this->container, [], $output);
            if ($code !== 0) {
                throw new \RuntimeException('migrate 失败: ' . $output->fetch());
            }

            return;
        } catch (\Throwable $e) {
            $fallback = $this->runMigrateCli();
            if ($fallback === null) {
                throw new \RuntimeException('执行迁移失败: ' . $e->getMessage(), 0, $e);
            }
            if ($fallback['code'] !== 0) {
                throw new \RuntimeException('migrate CLI 失败: ' . $fallback['output']);
            }
        }
    }

    /**
     * @return array{code: int, output: string}|null
     */
    private function runMigrateCli(): ?array
    {
        $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $bin = BASE_PATH . '/bin/hyperf.php';
        if (! is_file($bin)) {
            return null;
        }
        $cmd = [$php, $bin, 'migrate'];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes, BASE_PATH, null, ['bypass_shell' => true]);
        if (! is_resource($proc)) {
            return null;
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return ['code' => $code, 'output' => trim($stdout . "\n" . $stderr)];
    }

    private function applyScripts(string $extractDir): void
    {
        $src = $extractDir . '/backend/scripts';
        if (! is_dir($src)) {
            return;
        }
        $files = glob($src . '/*.php') ?: [];
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            include $file;
        }
    }

    private function applyOverlay(string $extractDir): void
    {
        $src = $extractDir . '/backend/overlay';
        if (! is_dir($src)) {
            return;
        }
        $root = BASE_PATH;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $rel = str_replace('\\', '/', $rel);
            if ($rel === false || $rel === '') {
                continue;
            }
            $dest = $this->safeJoin($root, $rel);
            if ($item->isDir()) {
                if (! is_dir($dest) && ! mkdir($dest, 0775, true) && ! is_dir($dest)) {
                    throw new \RuntimeException('无法创建 overlay 目录: ' . $rel);
                }
                continue;
            }
            if (! copy($item->getPathname(), $dest)) {
                throw new \RuntimeException('覆盖文件失败: ' . $rel);
            }
        }
    }

    private function applyFrontend(string $extractDir, string $taskId): void
    {
        $frontendDir = trim((string) env('CLOUD_UPGRADE_FRONTEND_DIR', ''));
        $src = $extractDir . '/frontend/dist';
        if ($frontendDir === '') {
            $this->store->update($taskId, [
                'append_log' => '警告: 未配置 CLOUD_UPGRADE_FRONTEND_DIR，跳过前端同步',
            ]);

            return;
        }
        if (! is_dir($src)) {
            $this->store->update($taskId, [
                'append_log' => '警告: 升级包无 frontend/dist，跳过前端同步',
            ]);

            return;
        }
        if (! is_dir($frontendDir) && ! mkdir($frontendDir, 0775, true) && ! is_dir($frontendDir)) {
            throw new \RuntimeException('无法创建前端目录: ' . $frontendDir);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $rel = str_replace('\\', '/', $rel);
            if ($rel === false || $rel === '') {
                continue;
            }
            $dest = $this->safeJoin($frontendDir, $rel);
            if ($item->isDir()) {
                if (! is_dir($dest) && ! mkdir($dest, 0775, true) && ! is_dir($dest)) {
                    throw new \RuntimeException('无法创建前端目录: ' . $rel);
                }
                continue;
            }
            if (! copy($item->getPathname(), $dest)) {
                throw new \RuntimeException('同步前端文件失败: ' . $rel);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                /** @var \SplFileInfo $item */
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
            @rmdir($dir);
        } catch (\Throwable) {
            // best-effort cleanup
        }
    }
}
