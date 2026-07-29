<?php
/**
 * 业务提示异常（用于向前端返回可读错误信息）
 */

namespace exceptions;

class TipException extends BaseException
{
    // HTTP 状态码
    public $code = 400;

    // 错误具体信息
    public $msg = '操作提示';

    public $message = '操作提示';

    // 自定义的错误码
    public $errorCode = 10000;

    /**
     * @param string|array $params 支持直接传字符串，或 ['msg'=>..., 'code'=>..., 'errorCode'=>...]
     */
    public function __construct($params = [])
    {
        if (is_string($params)) {
            $this->msg = $params;
            $this->message = $params;
            \Exception::__construct($params, (int) $this->code);
            return;
        }

        if (is_array($params)) {
            if (array_key_exists('code', $params)) {
                $this->code = $params['code'];
            }
            if (array_key_exists('msg', $params)) {
                $this->msg = $params['msg'];
                $this->message = $params['msg'];
            }
            if (array_key_exists('errorCode', $params)) {
                $this->errorCode = $params['errorCode'];
            }
            \Exception::__construct((string) $this->msg, (int) $this->code);
            return;
        }

        \Exception::__construct((string) $this->msg, (int) $this->code);
    }
}
