<?php
/**
 * 溯源防伪系统
 * =========================================================
 * 官方网址：http://www.flyinginternet.cn
 * QQ 交流群：728615087
 * Version：2.0
 */

namespace app\controller\auth;
use app\model\User as UserModel;
use app\services\TokenService;
use app\validate\UserRegisterValidate;
use Exception;

//公众号Token
class Html extends TokenService
{

    //获取token，openid
    public function getToken()
    {
        return $this->grantToken();
    }

    //用户登录
    public function login()
    {
        $validate = new UserRegisterValidate();
        $validate->scene('login')->goCheck();
        $post = $validate->getDataByRule(input('post.'));//过滤非验证器中的字段
        $pwd  = password($post['pwd']);
        $user = UserModel::where(['mobile'=> $post['mobile'],'pwd'=>$pwd])->find();
        if($user){
            $uid = $user->id;
            $cachedValue = $this->setHtmlCache($uid);
            $token = TokenService::saveCache($cachedValue);
            if($token){
                return app('json')->success($token);
            }
            throw new Exception('登录失败');
        }else{
            throw new Exception('用户名或密码错误');
        }
    }

    //uid放入缓存，$token做缓存键名;
    private function grantToken(){

        $newuser = UserModel::create([
            'user_type' => 3,
            'nickname' => '游客',
        ]);
        $uid = $newuser->id;

        $cachedValue = $this->setHtmlCache($uid);
        $token = TokenService::saveCache($cachedValue);
        return $token;
    }
    //组合uid，openid，权限
    private function setHtmlCache($uid){
        $cache['uid'] = $uid;
        $cache['token_type'] = 'HTML';
        $cache['scope'] = 9;  // 推荐用枚举
        $cache['user_type'] = 3;  // 推荐用枚举
        return $cache;
    }

}