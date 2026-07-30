<?php

declare(strict_types=1);

namespace App\Http\Admin\Middleware;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Http\CurrentUser;
use App\Library\Auth\FounderGate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 创始人门禁：平台 user_id === 1 或角色 code 含 founder；租户上下文一律拒绝。
 */
final class FounderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CurrentUser $currentUser,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! FounderGate::allows($this->currentUser->id(), $this->currentUser->user())) {
            throw new BusinessException(ResultCode::FORBIDDEN, '仅创始人可访问');
        }

        return $handler->handle($request);
    }
}
