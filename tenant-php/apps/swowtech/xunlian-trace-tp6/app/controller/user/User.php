<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 9:03
 */

namespace app\controller\user;


use app\model\User as UserModel;
use app\model\Order;
use app\services\TokenService;
use app\services\WxNotifyService;
use app\validate\UserRegisterValidate;
use bases\BaseController;
use app\controller\common\MiniWechat;
use Exception;


class User extends BaseController
{

    //用户注册
    public function register()
    {
        $validate = new UserRegisterValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        $user = UserModel::where('mobile',$post['mobile'])->find();
        if($user){
            throw new Exception('用户已注册');
        }
        $post['pwd']  = password($post['pwd']);
        $post['nickname'] = '手机用户';
        $res = UserModel::create($post);
        return app('json')->success($res);
    }

    /**
     * 获取用户基础信息
     */
    public function getInfo()
    {
        $uid = TokenService::getCurrentUid();
        $res=UserModel::field('*')->find($uid);
		return app('json')->success($res);
    }
    /**
     * 更新用户基础信息
     */
    public function updateUserInfo()
    {
        $uid = TokenService::getCurrentUid();

        $name = request()->post('name');
        $phone = request()->post('phone');
        $password = request()->post('password');

        $res=UserModel::update(['mobile'=>$phone],['id'=>$uid]);
        return json($res);
    }
    /**
     * h5用户申请注册企业
     */
    public function userApply()
    {
        $uid = TokenService::getCurrentUid();
        $mobile = request()->post('mobile');
        $res=UserModel::update(['mobile'=>$mobile],['id'=>$uid]);
        return json($res);
    }
    /**
     * 保存位置
     */
    public function savePosition($result=null,$region=null,$longitude=null,$latitude=null)
    {
        $uid = TokenService::getCurrentUid();
        if($result){
            $update_data = [
                'longitude'=>$result['longitude'],
                'latitude'=>$result['latitude'],
                'province' =>'',
                'city' =>'',
                'county' =>''
            ];
        }else{
            $update_data = [
                'longitude'=>$longitude,
                'latitude'=>$latitude,
                'province' =>$region[0],
                'city' =>$region[1],
                'county' =>$region[2]
            ];
        }
        $user = UserModel::find($uid);
        $res=$user->save($update_data);
        return app('json')->success('ok');
    }

    /**
     * 获取用户电话号码
     */
    public function getPhone($detail)
    {
        $uid = TokenService::getCurrentUid();
        if(!$uid){
            return app('json')->fail('用户id为空');
        }

        $detail = json_decode($detail);
        if($detail->errMsg != "getPhoneNumber:ok"){
            return app('json')->fail('客户端获取电话号码错误');
        }

        $app = MiniWechat::getMiniWechat();
        $session_key = TokenService::getCurrentTokenVar('session_key');

        $decryptedData = $app->encryptor->decryptData($session_key, $detail->iv, $detail->encryptedData);

        return app('json')->success($decryptedData);
    }

    public function wechatPay(){
        return view('wechat_pay', [
            'name'  => 'ThinkPHP',
            'email' => 'thinkphp@qq.com'
        ]);
    }
}
