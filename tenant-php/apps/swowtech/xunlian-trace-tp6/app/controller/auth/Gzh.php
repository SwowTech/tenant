<?php
/**
 * 溯源防伪系统
 * =========================================================
 * 官方网址：http://www.flyinginternet.cn
 * QQ 交流群：728615087
 * Version：2.0
 */

namespace app\controller\auth;
use app\model\SysConfig as SysConfigModel;
use app\model\User as UserModel;
use app\services\TokenService;
use exceptions\BaseException;
use exceptions\TokenException;
use think\facade\Log;

//公众号Token
class Gzh extends TokenService
{
    protected $code;
    public $gzhAppID;
    public $gzhAppSecret;
    public $wxExpire;
    protected $wxCodeUrl;
    //若提示“该链接无法访问”，请检查参数是否填写错误，是否拥有scope参数对应的授权作用域权限。
    //静默 scope=snsapi_base
    //手动 scope=snsapi_userinfo
    protected $gzh_code_url= "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
    protected $gzh_jm_code_url= "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
    //公众号登录，携带code换取openid
    protected $gzh_login_url='https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';


    function __construct()
    {
        $where['key']=['gzh_appid','gzh_secret','wx_token_expire'];
        $data = SysConfigModel::where($where)->select();
        if(!$data || count($data)!=3){
            throw new TokenException(['msg'=>'API中未填写配置信息']);
        }
        $arr=[];
        foreach ($data as $k=>$v){
            $arr[$v['key']]=$v['value'];
        }
        $this->gzhAppID = $arr['gzh_appid'];
        $this->gzhAppSecret = $arr['gzh_secret'];
        $this->wxExpire = $arr['wx_token_expire'];
    }

    //记得公众号平台设置：IP白名单 和 授权域名；ip是服务器IP，域名是前端域名
    public function getCodeUrl($url,$type)
    {
        //sprintf函数是把百分号（%）符号替换成一个作为参数进行传递的变量：%s=字符串,%u=正整数
        if($type=='userinfo') {
            $wxCodeUrl = sprintf($this->gzh_code_url, $this->gzhAppID, $url);
        }else{
            $wxCodeUrl = sprintf($this->gzh_jm_code_url,$this->gzhAppID,$url);
        }
        return $wxCodeUrl;
    }
    //获取token，openid
    public function getToken($code,&$userinfo)
    {
        $wxLoginUrl = sprintf($this->gzh_login_url,$this->gzhAppID,$this->gzhAppSecret,$code);
        //注意code是临时的，所以向微信服务器提交只能使用一次
        $result = curl_get($wxLoginUrl);
        $wxResult = json_decode($result, true);
        if (empty($wxResult))
        {
            throw new TokenException(['msg'=>'获取session_key及openID时异常，微信内部错误']);
        }else{
            $loginFail = array_key_exists('errcode', $wxResult);
            if ($loginFail) {
                $this->wxError($wxResult);
            } else {
                return $this->grantToken($wxResult,$userinfo);
            }
        }
    }
    //openid，uid放入缓存，$token做缓存键名;
    private function grantToken($wxResult,&$userinfo){

        $openid = $wxResult['openid'];
        $access_token = $wxResult['access_token'];
        $userinfo=$this->getUserInfo($openid,$access_token);
        Log::error($userinfo);
        $fuser = UserModel::where('openid_gzh',$openid)->find();
        if(!$fuser){
            if($userinfo){
                //新建用户openid，头像，昵称
                $newuser = UserModel::create([
                    'openid_gzh' => $openid,
                    'nickname'=>$userinfo['nickname'],
                    'headpic'=>$userinfo['headpic'],
                    'user_type' => 2,
                    'web_auth_id' => 0
                ]);
            }else {
                Log::error('未取得微信信息');
                //新建用户openid
                $newuser = UserModel::create([
                    'openid_gzh' => $openid,
                    'user_type' => 2
                ]);
            }
            if(!$newuser->id){
                throw new BaseException(['msg'=>'用户注册失败']);
            }
            $uid = $newuser->id;
        }else{
            if( $userinfo){
                //更新用户头像，昵称
                $fuser->nickname= $nickname=$userinfo['nickname'];
                $fuser->headpic=$userinfo['headpic'];
                $fuser->save();
                $userinfo['is_member'] = $fuser->is_member;
            }else{
                Log::error('未取得微信信息');
            }
            $uid = $fuser->id;
        }
        $cachedValue = $this->setWxCache($openid,$uid);
        $token = TokenService::saveCache($cachedValue);
        return $token;
    }
    //组合uid，openid，权限
    private function setWxCache($openid, $uid){
        $cache['openid'] = $openid;
        $cache['uid'] = $uid;
        $cache['token_type'] = "H5";
        $cache['scope'] = 9;  // 推荐用枚举
        return $cache;
    }
    private function getUserInfo($openid,$access_token)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
        $userinfo_url = sprintf($url, $access_token, $openid);
        $res = curl_get($userinfo_url);
        $userinfo = json_decode($res, true);

        if(isset($userinfo['errcode'])){
            Log::error($userinfo);
        }

        if (!isset($userinfo['nickname'])) {
            return false;
        }

        $arr['nickname']=$userinfo['nickname'];
        $arr['headpic']=$userinfo['headimgurl'];
        return $arr;
    }


    //微信错误信息解析后抛出
    private function wxError($wxResult)
    {
        throw new TokenException(
            [
                'msg' => $wxResult['errmsg'],
                'errorCode' => $wxResult['errcode']
            ]);
    }

}
