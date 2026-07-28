<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 优先从宿主 storage/uploads、public/uploads 提供 /uploads 静态文件，
 * 并兼容各应用 public/uploads 本地回退目录，
 * 避免独立域名把上传路径反代进应用却找不到文件。
 */
final class ServeHostUploadsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (! str_starts_with($path, '/uploads/')) {
            return $handler->handle($request);
        }

        $rel = ltrim(substr($path, strlen('/uploads/')), '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return $handler->handle($request);
        }

        $file = $this->resolveFile($rel);
        if ($file === null) {
            return $handler->handle($request);
        }

        $mime = (string) (@mime_content_type($file) ?: 'application/octet-stream');
        $body = (string) file_get_contents($file);
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus(200)
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) strlen($body))
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withBody(new SwooleStream($body));

        return $response;
    }

    private function resolveFile(string $rel): ?string
    {
        $candidates = [
            BASE_PATH . '/storage/uploads/' . $rel,
            BASE_PATH . '/public/uploads/' . $rel,
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $appsRoot = BASE_PATH . '/apps';
        if (! is_dir($appsRoot)) {
            return null;
        }
        foreach (glob($appsRoot . '/*', GLOB_ONLYDIR) ?: [] as $vendorDir) {
            foreach (glob($vendorDir . '/*', GLOB_ONLYDIR) ?: [] as $appDir) {
                $candidate = $appDir . '/public/uploads/' . $rel;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
