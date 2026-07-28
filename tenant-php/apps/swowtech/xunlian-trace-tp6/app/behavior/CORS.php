<?php
namespace app\behavior;


use think\Response;

class CORS
{
    public function handle($event)
    {
        header('X-Powered-By: WAF/2.0');
        header("access-control-allow-headers: *");
        header("access-control-allow-methods: *");
        header("access-control-allow-credentials: false");
        $origin = request()->header('origin');
        header('Access-Control-Allow-Origin: ' . $origin);
        if (request()->method(true) == 'OPTIONS') {
            $response = Response::create('ok')->code(200);
            $response->send();
            app('http')->end($response);
            die();
        }
    }
}