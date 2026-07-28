<?php

declare(strict_types=1);

namespace App\Service\Cloud;

final class CloudUpgradeTaskStore
{
    private const FLOCK_FILE = '.lock';

    private const RUNNING_LOCK = 'running.lock';

    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? (BASE_PATH . '/runtime/cloud-upgrade');
    }

    public function dir(): string
    {
        return $this->dir;
    }

    /**
     * @param array{target_version?: string, status?: string} $payload
     * @return array<string, mixed>
     */
    public function create(array $payload = []): array
    {
        $this->ensureDir();
        $task = $this->newTask($payload);
        $this->write($task);

        return $task;
    }

    /**
     * Create a task only when no upgrade is running (flock + running.lock sentinel).
     *
     * @param array{target_version?: string, status?: string} $payload
     * @return array<string, mixed>
     */
    public function createIfNotRunning(array $payload = []): array
    {
        $this->ensureDir();
        $lockPath = $this->dir . '/' . self::FLOCK_FILE;
        $fp = fopen($lockPath, 'c+');
        if ($fp === false) {
            throw new \RuntimeException('无法打开升级锁');
        }
        try {
            if (! flock($fp, LOCK_EX)) {
                throw new \RuntimeException('无法获取升级锁');
            }
            if ($this->hasRunningUnlocked()) {
                throw new \RuntimeException('已有升级任务进行中');
            }
            $task = $this->newTask($payload);
            $this->write($task);
            $runningLock = $this->dir . '/' . self::RUNNING_LOCK;
            if (file_put_contents($runningLock, (string) $task['id'], LOCK_EX) === false) {
                @unlink($this->path((string) $task['id']));
                throw new \RuntimeException('无法创建运行锁');
            }

            return $task;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function clearRunningLock(): void
    {
        @unlink($this->dir . '/' . self::RUNNING_LOCK);
    }

    /**
     * @param array<string, mixed> $patch
     * @return array<string, mixed>
     */
    public function update(string $id, array $patch): array
    {
        $task = $this->get($id);
        if ($task === null) {
            throw new \RuntimeException('升级任务不存在: ' . $id);
        }
        foreach (['status', 'step', 'progress', 'message', 'target_version'] as $key) {
            if (array_key_exists($key, $patch)) {
                $task[$key] = $patch[$key];
            }
        }
        if (isset($patch['logs']) && is_array($patch['logs'])) {
            $task['logs'] = $patch['logs'];
        }
        if (isset($patch['append_log']) && is_string($patch['append_log']) && $patch['append_log'] !== '') {
            $logs = is_array($task['logs'] ?? null) ? $task['logs'] : [];
            $logs[] = [
                'at' => date('c'),
                'message' => $patch['append_log'],
            ];
            $task['logs'] = $logs;
        }
        $task['updated_at'] = date('c');
        $this->write($task);

        return $task;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
        if ($id === '') {
            return null;
        }
        $path = $this->path($id);
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    public function hasRunning(): bool
    {
        $this->ensureDir();
        if (is_file($this->dir . '/' . self::RUNNING_LOCK)) {
            return true;
        }

        return $this->hasRunningUnlocked();
    }

    private function hasRunningUnlocked(): bool
    {
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $raw = file_get_contents($file);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (! is_array($data)) {
                continue;
            }
            $status = (string) ($data['status'] ?? '');
            if (in_array($status, ['pending', 'running'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{target_version?: string, status?: string, step?: string, progress?: int, message?: string} $payload
     * @return array<string, mixed>
     */
    private function newTask(array $payload): array
    {
        $now = date('c');

        return [
            'id' => bin2hex(random_bytes(8)),
            'status' => (string) ($payload['status'] ?? 'pending'),
            'step' => (string) ($payload['step'] ?? 'pending'),
            'progress' => (int) ($payload['progress'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'logs' => [],
            'target_version' => (string) ($payload['target_version'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latest(): ?array
    {
        $this->ensureDir();
        $best = null;
        $bestTs = 0;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $raw = file_get_contents($file);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (! is_array($data)) {
                continue;
            }
            $ts = strtotime((string) ($data['updated_at'] ?? $data['created_at'] ?? '')) ?: 0;
            if ($ts >= $bestTs) {
                $bestTs = $ts;
                $best = $data;
            }
        }

        return $best;
    }

    private function ensureDir(): void
    {
        if (! is_dir($this->dir) && ! mkdir($this->dir, 0775, true) && ! is_dir($this->dir)) {
            throw new \RuntimeException('无法创建升级任务目录: ' . $this->dir);
        }
    }

    /**
     * @param array<string, mixed> $task
     */
    private function write(array $task): void
    {
        $this->ensureDir();
        $id = (string) ($task['id'] ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException('任务 id 不能为空');
        }
        $path = $this->path($id);
        $tmp = $path . '.tmp';
        $json = json_encode($task, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($tmp, $json) === false) {
            throw new \RuntimeException('写入升级任务失败');
        }
        if (! rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('保存升级任务失败');
        }
    }

    private function path(string $id): string
    {
        return $this->dir . '/' . $id . '.json';
    }
}
