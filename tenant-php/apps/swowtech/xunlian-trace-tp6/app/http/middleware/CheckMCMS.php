<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/30 0030
 * Time: 13:23
 */

namespace app\http\middleware;

use app\model\User;
use app\services\TokenService;
use exceptions\BaseException;

class CheckMCMS
{
    public function handle($request, \Closure $next)
    {
        $uid= TokenService::getCurrentUid();//判断是否具有管理员权限

        if($uid) {
            return $next($request);
        }else{
            throw new BaseException(['msg'=>'无权限']);
        }
    }
}