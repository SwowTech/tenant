<?php
namespace app\behavior;

use think\Response;

class CORS
{
    public function handle($event)
    {
        header('X-Powered-By: WAF/2.0');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Credentials: false');
        $origin = '';
        try {
            $origin = (string) (request()->header('origin') ?: '');
        } catch (\Throwable) {
            $origin = '';
        }
        // 无 Origin 时不要写空值（部分代理会异常）
        header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
        try {
            if (strtoupper((string) request()->method(true)) === 'OPTIONS') {
                $response = Response::create('ok')->code(200);
                $response->send();
                app('http')->end($response);
                exit;
            }
        } catch (\Throwable) {
        }
    }
}
