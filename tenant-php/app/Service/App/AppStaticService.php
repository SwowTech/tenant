<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

final class AppStaticService
{
    public function response(string $identifier, string $relPath): ResponseInterface
    {
        $manifest = AppManifest::load($identifier);
        $webRoot = AppManifest::webDir($manifest, $identifier);
        $relPath = str_replace('\\', '/', $relPath);
        $relPath = ltrim($relPath, '/');
        if ($relPath === '' || str_contains($relPath, '..')) {
            $relPath = 'index.html';
        }
        $file = $webRoot . '/' . $relPath;
        if (is_dir($file)) {
            $file = rtrim($file, '/\\') . '/index.html';
        }
        if (! is_file($file)) {
            $file = $webRoot . '/index.html';
        }
        if (! is_file($file)) {
            return $this->plain(404, 'not found');
        }
        $body = file_get_contents($file);
        if ($body === false) {
            return $this->plain(500, 'read error');
        }
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus(200);
        $response = $response->withHeader('Content-Type', $this->mime($file));
        $response = $response->withBody(new SwooleStream($body));

        return $response;
    }

    private function plain(int $status, string $text): ResponseInterface
    {
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus($status);
        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response = $response->withBody(new SwooleStream($text));

        return $response;
    }

    private function mime(string $file): string
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return match ($ext) {
            'html', 'htm' => 'text/html; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
    }
}
