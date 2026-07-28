<?php

/**
 * 防伪溯源系统
 * =========================================================
 * 官方网址：http://www.xunlianshop.com
 * QQ 交流群：728615087
 * Version：1.2.0
 */
namespace app\http\middleware;

//中间件，验证token，检测权限

use app\services\TokenService;

class CheckCms
{
    public function handle($request, \Closure $next)
    {
        $res = TokenService::GTadmimScope();//判断是否具有管理员权限
        if($res == true) {
            return $next($request);
        }
    }
}