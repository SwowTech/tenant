<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Service\Setting\SiteSettingService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * When site is closed, block non-admin traffic (apps / public API).
 * Admin backend remains reachable so operators can reopen.
 */
final class SiteClosedMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SiteSettingService $site) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($this->isBypassPath($path)) {
            return $handler->handle($request);
        }

        $cfg = $this->site->get();
        if (! ($cfg['closed'] ?? false)) {
            return $handler->handle($request);
        }

        $reason = trim((string) ($cfg['close_reason'] ?? ''));
        if ($reason === '') {
            $reason = '站点维护中，请稍后再试';
        }

        throw new BusinessException(ResultCode::DISABLED, $reason);
    }

    private function isBypassPath(string $path): bool
    {
        return str_starts_with($path, '/admin')
            || str_starts_with($path, '/internal')
            || str_starts_with($path, '/swagger')
            || str_starts_with($path, '/favicon');
    }
}
