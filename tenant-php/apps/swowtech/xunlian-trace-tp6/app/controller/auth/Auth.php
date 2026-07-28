<?php

namespace app\controller\auth;


use app\model\Region;
use app\model\User as UserModel;
use app\services\TokenService;
use EasyWeChat\Factory;
use exceptions\TokenException;

class Auth
{
    /********************  公众号  ************************/
    //请求公众号code
    public function wxcodeUrl($url, $type = '')
    {
        $gzh_service = new Gzh();
        $res = $gzh_service->getCodeUrl($url, $type);
        return $res;
    }

    //获取js
    public function getWxJsKey(){
        $gzh_service = new Gzh();

        $config = [
            'app_id' => $gzh_service->gzhAppID,
            'secret' => $gzh_service->gzhAppSecret,

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
        ];

        $app = Factory::officialAccount($config);
                $app->jssdk->setUrl(get_public_url().'/h5/');
        //$app->jssdk->setUrl('http://127.0.0.1:8080/h5#/');
        $res = $app->jssdk->buildConfig([
            'scanQRCode',
            'getLocation',
            'checkJsApi',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
        ], false, false,false);

        return app('json')->success('',$res);
    }

    //请求应用类型，公众号或普通网页,
    public function getAppType()
    {
        $res['app_type'] = system_app_type();
        return json($res);
    }

    public function gzhToken($code='',$type="GZH")
    {
        //无公众号获取token
        if($type == 'html'){
            $usertoken = new Html;
            $token = $usertoken->getToken();
            $data['token'] = $token;
            return json($data);
        }else{
            if(!$code){
                return app('json')->fail('code不能为空');
            }
            $usertoken = new Gzh;
            $token = $usertoken->getToken($code,$userinfo);
            $data['token'] = $token;
            $data['userinfo'] = $userinfo;
            return json($data);
        }

    }


    /********************  小程序 ↓  ************************/
    /*
        * 用途：将“openid，uid，权限”存入缓存value，生成一个token做缓存的key并返回
        * 1、获取code
        * 2、组合code,Appid与Secret生成URL，
        * 3、curl方式向微信服务器提交，获取openid;注意一个code只能使用一次
        * 4、判断openid，数据库不存在则写入；从数据库获取该openid的用户UID
        * 5、生成token，token是一个随机字符串，它是缓存的key；将“openid，uid，权限”存入缓存value
        * 6、返回token
        */
    public function getXcxToken($code)
    {
        $usertoken = new Xcx($code);
        $token = $usertoken->getToken();
        $data = ['token' => $token];
        return json($data);
    }
    //获取app的token
    public function getAppToken($code)
    {
        $usertoken = new App($code);
        $token = $usertoken->getToken();
        $data = ['token' => $token];
        return json($data);
    }
    public function updateXcxUserInfo($userInfo)
    {
        $uid = TokenService::getCurrentUid();
        $nickname = $userInfo['nickName'];
        $headpic = $userInfo['avatarUrl'];
        $gender = $userInfo['gender'];

        $city = strtolower($userInfo['city']);
        $province= strtolower($userInfo['province']);
        $country = strtolower($userInfo['country']);
        $city_name = Region::where('pinyin',$city)->value('name');
        if($city_name){
            $city = $city_name;
        }
        $province_name = Region::where('pinyin',$province)->value('name');
        if($province_name){
            $province = $province_name;
        }
        if($country=='china'){
            $country = '中国';
        }
        $nickname = $userInfo['nickName'];
        $res = UserModel::where('id', $uid)
            ->update([
                'nickname' => $nickname,
                'headpic' => $headpic,
                'city' => $city,
                'province' => $province,
                'country' => $country,
                'gender' => $gender
            ]);
        $res = UserModel::where('id', $uid)->find();
        return app('json')->success($res);
    }
    /********************  微信 + 小程序 共用  ************************/
    //验证token，返回false,true
    public function verifyToken($token)
    {
        if (!$token) {
            throw new TokenException(['msg' => 'token不允许为空']);
        }
        $valid = Token::verifyToken($token);
        $arr = ['isValid' => $valid];
        return json($arr);
    }

}
