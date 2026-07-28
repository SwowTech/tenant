<?php


namespace app\services;


use app\model\Admin as AdminModel;
use app\model\User;
use app\model\User as UserModel;
use enum\ScopeEnum;
use exceptions\TokenException;
use think\Exception;
use think\facade\Cache;
use think\facade\Request;

//API的token文件，用于：生成token、
class TokenService
{
    protected static $tokenExpire;

    function __construct()
    {
        self::$tokenExpire = config('setting.token_expire_in'); //token缓存有效时间
    }

    //生成随机token
    public static function generateToken()
    {
        //32个字符组成一组随机字符串
        $randChars = self::getRandChar(32);
        //用三组字符串，进行md5加密
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = config('secure.token_salt');
        return md5($randChars . $timestamp . $salt);
    }

    //通过token获取该条缓存数据中指定的字段
    public static function getCurrentTokenVar($key)
    {
        $token = Request::header('token');
        if (!$token) {
            throw new TokenException();
        }
        $vars = Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new TokenException(['msg' => '尝试获取的变量并不存在']);
            }
        }
    }

    //通过token获取该条缓存数据中指定的字段
    public static function getCTVar($key)
    {
        $token = Request::header('token');
        $vars = Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                return false;
            }
        }
    }

    //放入缓存
    public static function saveCache($cachedValue)
    {
        $key = self::generateToken();//生成token
        $value = json_encode($cachedValue);
        $expire = self::$tokenExpire ?: (int) config('setting.token_expire_in');
        $request = cache($key, $value, $expire);//第三参数是时效期
        if (!$request) {
            throw new TokenException(['msg' => '服务器缓存异常']);
        }
        return $key;
    }
    //取出缓存
    public static function getCache()
    {
        $token = Request::header('token');
        if ($token=='null') {
            throw new TokenException();
        }
        $request = cache($token);
        if (!$request) {
            throw new TokenException(['msg' => '服务器缓存异常']);
        }
        return $request;
    }
    //通过token获取uid
    public static function getCurrentUid()
    {
        $uid = self::getCurrentTokenVar('uid');
        $user = UserModel::find($uid);
        if (!$user) {
            throw new TokenException(['msg' => '无该用户信息，正在登录']);
        }
        return $uid;
    }

    //通过token获取cms的admin_id
    public static function getCurrentAid()
    {
        $admin_id = self::getCurrentTokenVar('admin_id');
        $admin = AdminModel::find($admin_id);
        if (!$admin) {
            throw new TokenException(['msg' => '无该管理员信息']);
        }
        return $admin_id;
    }

    //通过token获取admin
    public static function getAdmin()
    {
        $uid = self::getCurrentTokenVar('admin_id');
        $admin = AdminModel::find($uid);
        if (!$admin) {
            throw new TokenException(['msg' => '无该管理员信息']);
        }
        return $admin;
    }

    //获取admin绑定的用户
    public static function getAdminBindUser()
    {
        $uid = self::getCurrentTokenVar('admin_id');
        $admin = AdminModel::find($uid);
        if ($admin && $admin['user_id']) {
           $user = User::find($admin['user_id']);
           if($user){
               return $user;
           }else{
               throw new TokenException(['msg' => '找不到绑定的用户信息']);
           }
        }else{
            throw new TokenException(['msg' => '无该管理员信息']);
        }
    }

    //通过token获取scope,并判断权限
    public static function GTuserScope()
    {
        $scope = self::getCurrentTokenVar('scope');
        if (!$scope) {
            throw new TokenException(['msg' => 'Token获取权限值失败']);
        }
        if ($scope >= ScopeEnum::User) { //判断权限值是否大于最低的用户权限
            return true;
        } else {
            throw new TokenException(['msg' => '无权限']);
        }
    }



    //判断权限，仅管理员可访问
    public static function GTadmimScope()
    {
        try {
            $scope = self::getCurrentTokenVar('scope');
            if ($scope >= ScopeEnum::Root) { //判断权限值是否大于最低的用户权限
                return true;
            } else {
                throw new TokenException(['msg' => '无权限']);
            }
        } catch (Exception $ex) {
            throw new TokenException(['msg' => '请登录']);
            //throw new AdminException(['msg'=>'无权限-'.$ex->getMessage(),'code'=>$ex->getCode()]);
        }
    }


    //生成token函数中调用的，生成随机字符串
    private static function getRandChar($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0;
             $i < $length;
             $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    //验证toen
    public static function verifyToken($token)
    {
        $exist = Cache::get($token);
        if ($exist) {
            return true;
        } else {
            return false;
        }
    }


    //在cache加入变量
    public static function AddCacheKeyValue($key, $value)
    {
        $token = Request::get('token');
        if($token){
            $vars = Cache::get($token);
            if(!is_array($vars)){
                $vars = json_decode($vars,true);
            }
            $vars[$key] = $value;
            $value = json_encode($vars);
            cache($token, $value, config('setting.token_expire_in'));//第三参数是时效期
            return true;
        }else{
            return false;
        }

    }

}