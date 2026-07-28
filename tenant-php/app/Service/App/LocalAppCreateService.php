<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Library\Cloud\SaasPublicClient;
use RuntimeException;
use Throwable;

/**
 * 创始人：创建本地应用（apps/{vendor}/{app} 脚手架）.
 */
final class LocalAppCreateService
{
    /**
     * SaaS 市场 + 本地目录查重.
     *
     * @return array{available: bool, local_exists: bool, saas_exists: bool, message: string}
     */
    public function checkIdentifier(string $identifier): array
    {
        try {
            $identifier = AppPath::assertSafeIdentifier($identifier);
        } catch (Throwable $e) {
            return [
                'available' => false,
                'local_exists' => false,
                'saas_exists' => false,
                'message' => '标识格式须为 vendor/app（字母数字_-）',
            ];
        }

        $localExists = is_dir(AppPath::appDir($identifier)) && is_file(AppPath::appDir($identifier) . '/app.json');
        $saasExists = $this->saasIdentifierExists($identifier);

        if ($localExists) {
            return [
                'available' => false,
                'local_exists' => true,
                'saas_exists' => $saasExists,
                'message' => '本地已存在该应用目录',
            ];
        }
        if ($saasExists) {
            return [
                'available' => false,
                'local_exists' => false,
                'saas_exists' => true,
                'message' => 'SaaS 云市场已存在相同标识，请更换',
            ];
        }

        return [
            'available' => true,
            'local_exists' => false,
            'saas_exists' => false,
            'message' => '可以使用',
        ];
    }

    /**
     * @param array{identifier: string, title?: string, version?: string, edition?: string, family?: string, with_demo?: bool} $data
     * @return array{identifier: string, title: string, version: string, path: string, origin: string}
     */
    public function create(array $data): array
    {
        $identifier = AppPath::assertSafeIdentifier((string) ($data['identifier'] ?? ''));
        $check = $this->checkIdentifier($identifier);
        if (! $check['available']) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, $check['message']);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $identifier;
        }
        $version = trim((string) ($data['version'] ?? '1.0.0'));
        if ($version === '') {
            $version = '1.0.0';
        }
        $edition = trim((string) ($data['edition'] ?? 'community'));
        $family = trim((string) ($data['family'] ?? $identifier));
        if ($family === '') {
            $family = $identifier;
        }
        $withDemo = array_key_exists('with_demo', $data)
            ? (bool) $data['with_demo']
            : true;

        $dir = AppPath::appDir($identifier);
        if (is_dir($dir) || is_file($dir)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '目标目录已存在');
        }

        $this->scaffold($identifier, $title, $version, $edition, $family, $withDemo);

        try {
            AppManifest::load($identifier);
        } catch (Throwable $e) {
            $this->removeDir($dir);
            throw new BusinessException(ResultCode::FAIL, '脚手架校验失败: ' . $e->getMessage());
        }

        return [
            'identifier' => $identifier,
            'title' => $title,
            'version' => $version,
            'path' => $dir,
            'origin' => 'local',
            'with_demo' => $withDemo,
        ];
    }

    private function saasIdentifierExists(string $identifier): bool
    {
        try {
            $data = SaasPublicClient::get('/store/apps/identifier-available', [
                'identifier' => $identifier,
            ], 5);
            if (isset($data['available'])) {
                return ! (bool) $data['available'];
            }
            if (isset($data['exists'])) {
                return (bool) $data['exists'];
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function scaffold(
        string $identifier,
        string $title,
        string $version,
        string $edition,
        string $family,
        bool $withDemo
    ): void {
        [$vendor, $app] = explode('/', $identifier, 2);
        $dir = AppPath::appDir($identifier);
        $ns = $this->phpNamespace($vendor, $app);
        $composerName = strtolower($vendor) . '/' . strtolower($app) . '-app';
        $issuer = 'app-member:' . $identifier;

        $this->mkdir($dir . '/bin');
        $this->mkdir($dir . '/app');
        $this->mkdir($dir . '/config');
        $this->mkdir($dir . '/public');
        $this->mkdir($dir . '/web');
        $this->mkdir($dir . '/storage');

        $manifest = [
            'name' => $identifier,
            'title' => $title,
            'version' => $version,
            'edition' => $edition,
            'family' => $family,
            'type' => 'app',
            'web' => ['path' => 'web'],
            'process' => ['entrypoint' => 'bin/start.php', 'listen' => 'tcp'],
            'api_prefix' => 'api',
            'auth' => ['member_jwt_issuer' => $issuer],
        ];
        $this->write($dir . '/app.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->write($dir . '/composer.json', json_encode([
            'name' => $composerName,
            'require' => ['php' => '>=8.1'],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->write($dir . '/bin/start.php', <<<'PHP'
<?php

/**
 * 应用进程标准入口（生产可换成 Hyperf/Swoole server）.
 * 宿主注入: APP_LISTEN=127.0.0.1:port  APP_GATEWAY_SECRET=...
 */
declare(strict_types=1);

$listen = getenv('APP_LISTEN') ?: '127.0.0.1:29100';
$appRoot = dirname(__DIR__);
$public = $appRoot . '/public';
$router = $public . '/index.php';
$php = PHP_BINARY;

$cmd = escapeshellarg($php) . ' -S ' . escapeshellarg($listen)
    . ' -t ' . escapeshellarg($public)
    . ' ' . escapeshellarg($router);

passthru($cmd, $code);
exit($code);

PHP);

        $this->write($dir . '/config/app.php', <<<PHP
<?php

declare(strict_types=1);

/**
 * 应用配置（独立进程内读取；不覆盖宿主）.
 */
return [
    'name' => '{$identifier}',
    'auth' => [
        'issuer' => '{$issuer}',
    ],
];

PHP);

        $this->write($dir . '/app/Application.php', <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

/**
 * 应用命名空间占位（完整业务可放在此目录）.
 */
final class Application
{
    public static function name(): string
    {
        return '{$identifier}';
    }
}

PHP);

        $this->write($dir . '/storage/.gitkeep', '');

        if ($withDemo) {
            $this->writeDemoPublic($dir, $identifier, $issuer, $title);
            $this->write($dir . '/web/index.html', $this->demoIndexHtml($title));
            $this->write($dir . '/web/admin.html', $this->demoAdminHtml($title));
        } else {
            $this->write($dir . '/public/index.php', <<<'PHP'
<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uri === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'not found';

PHP);
            $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->write($dir . '/web/index.html', <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{$safeTitle}</title>
</head>
<body>
  <h1>{$safeTitle}</h1>
  <p>本地应用已创建，可在此目录扩展前端与 API。</p>
</body>
</html>

HTML);
            $this->write($dir . '/web/admin.html', <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <title>{$safeTitle} 管理</title>
</head>
<body>
  <h1>应用自管后台</h1>
  <p><a href="index.html">返回前台</a></p>
</body>
</html>

HTML);
        }
    }

    private function writeDemoPublic(string $dir, string $identifier, string $issuer, string $title): void
    {
        $demoSrc = AppPath::root() . '/mineadmin/demo/public/index.php';
        if (is_file($demoSrc)) {
            $code = (string) file_get_contents($demoSrc);
            $code = str_replace(
                ['mineadmin/demo', 'app-member:mineadmin/demo'],
                [$identifier, $issuer],
                $code
            );
            $this->write($dir . '/public/index.php', $code);

            return;
        }

        // fallback minimal if demo missing
        $this->write($dir . '/public/index.php', "<?php\necho 'ok';\n");
    }

    private function demoIndexHtml(string $title): string
    {
        $safe = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{$safe}</title>
</head>
<body>
  <h1>{$safe}</h1>
  <p><a href="admin.html">应用自管后台</a></p>
  <form id="login">
    <input name="username" placeholder="用户名" required />
    <input name="password" type="password" placeholder="密码" required />
    <button type="submit">登录</button>
  </form>
  <pre id="out"></pre>
  <script>
    document.getElementById('login').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const res = await fetch('api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: fd.get('username'), password: fd.get('password') }),
      });
      document.getElementById('out').textContent = await res.text();
    });
  </script>
</body>
</html>

HTML;
    }

    private function demoAdminHtml(string $title): string
    {
        $safe = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <title>{$safe} 管理</title>
</head>
<body>
  <h1>应用自管后台</h1>
  <p>管理端由应用定义，非宿主平台。</p>
  <p><a href="index.html">返回前台</a></p>
</body>
</html>

HTML;
    }

    private function phpNamespace(string $vendor, string $app): string
    {
        return 'App\\' . $this->studly($vendor) . '\\' . $this->studly($app);
    }

    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', strtolower($value));
        $value = ucwords($value);

        return str_replace(' ', '', $value);
    }

    private function mkdir(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException('无法创建目录: ' . $path);
        }
    }

    private function write(string $path, string $contents): void
    {
        $parent = dirname($path);
        $this->mkdir($parent);
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('无法写入: ' . $path);
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
