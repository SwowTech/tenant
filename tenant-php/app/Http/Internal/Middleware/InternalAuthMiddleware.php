<?php

declare(strict_types=1);

namespace App\Http\Internal\Middleware;

use App\Http\Common\Result;
use App\Http\Common\ResultCode;
use Hyperf\Codec\Json;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swow\Psr7\Message\ResponsePlus;
use Swow\Psr7\Message\ResponsePlusInterface;

final class InternalAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ConfigInterface $config) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('X-Internal-Token');
        $expected = (string) $this->config->get('internal.token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            return $this->jsonResponse(new Result(ResultCode::UNAUTHORIZED, 'Invalid internal token'));
        }

        $allowedIps = $this->config->get('internal.allowed_ips', ['127.0.0.1', '::1']);
        if (is_array($allowedIps) && $allowedIps !== []) {
            $clientIp = $request->getServerParams()['remote_addr'] ?? '';
            if ($clientIp !== '' && ! in_array($clientIp, $allowedIps, true)) {
                return $this->jsonResponse(new Result(ResultCode::FORBIDDEN, 'IP not allowed'));
            }
        }

        return $handler->handle($request);
    }

    private function jsonResponse(Result $result): ResponsePlusInterface
    {
        return (new ResponsePlus('HTTP/1.1', 200))
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(new SwooleStream(Json::encode($result->toArray())));
    }
}
