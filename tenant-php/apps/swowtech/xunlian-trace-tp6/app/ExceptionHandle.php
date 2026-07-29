<?php
namespace app;

use exceptions\TipException;
use exceptions\TokenException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    protected $ignoreReport = [
        ValidateException::class,
        TipException::class,
        TokenException::class,
    ];

    public function report(Throwable $exception): void
    {
        if (!$this->isIgnoreReport($exception)) {
            $data = [
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code'    => $this->getCode($exception),
            ];
            $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            try {
                $this->app->log->record($log, 'error');
            } catch (\Throwable $e) {
            }
        }
    }

    public function render($request, Throwable $e): Response
    {
        // 避免把 Exception 对象/巨大 trace 塞进 JSON（会导致内存耗尽 → nginx 502）
        $safeData = null;
        if ($this->app->isDebug()) {
            $safeData = [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ];
        }

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($e instanceof TokenException) {
            $code = $e->getCode() ?: 401;
            return app('json')->code(401)->make($code, $e->getMessage() ?: '请登录', $safeData);
        }

        if ($e instanceof ValidateException) {
            return app('json')->fail($e->getError(), $safeData);
        }

        if ($e instanceof TipException) {
            $msg = $e->msg ?: $e->getMessage() ?: '操作失败';
            return app('json')->code(400)->fail($msg, $safeData);
        }

        $httpCode = ($e instanceof HttpException) ? $e->getStatusCode() : 400;
        $msg = $e->getMessage() ?: '服务器错误';
        // HTTP 用 200，业务码放 JSON，避免反代/CDN 丢掉 5xx 响应体
        $biz = $httpCode >= 400 ? $httpCode : 400;

        return app('json')->code(200)->make($biz, $msg, $safeData);
    }
}
