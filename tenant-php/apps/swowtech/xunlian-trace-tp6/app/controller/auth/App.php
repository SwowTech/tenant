<?php

namespace app\controller\auth;

use app\model\User as UserModel;
use app\services\TokenService;
use exceptions\TokenException;

//小程序Token
class App extends TokenService
{
    protected $code;


    function __construct($code='')
    {
        $this->code = $code;
        parent::__construct();
    }


    //获取token，openid
    public function getToken()
    {

        //注意code是临时的，所以向微信服务器提交只能使用一次
        $wxResult = $this->code;
        if (empty($wxResult))
        {
            throw new TokenException(['msg'=>'获取session_key及openID时异常，微信内部错误']);
        }else{
            return $this->grantToken($wxResult);
        }
    }
    //openid，uid放入缓存，$token做缓存键名;
    private function grantToken($wxResult){
        $openid = $wxResult['authResult']['openid'] ;
        $user_id = UserModel::where('openid',$openid)->value('id');
        if($user_id){
            $uid = $user_id;
        } else{
            $uid = $this->newUser($openid);
        }
        $cachedValue = $this->setWxCache($wxResult['authResult'],$uid);
        $token = TokenService::saveCache($cachedValue);
        return $token;
    }
    //组合uid，openid，权限
    private function setWxCache($wxResult,$uid){
        $cache = $wxResult; //微信的3个返回值
        $cache['uid'] = $uid;
        $cache['scope'] = 9;
        $cache['token_type'] = "App";
        $cache['wxResult'] = $wxResult;
        return $cache;
    }

    private function newUser($openid){
        $user = UserModel::create([
            'openid' => $openid,
            'user_type' => '1'
        ]);
        return $user->id;
    }

}
