<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/22 0022
 * Time: 9:03
 */

namespace app\controller\common;


use app\model\SysConfig as SysConfigModel;
use bases\BaseController;
use EasyWeChat\Factory;
use exceptions\TokenException;


class MiniWechat extends BaseController
{

    /**
     * 获取easywechat微信小程序实例
     * @param $id
     * @return \EasyWeChat\MiniProgram\Application
     */
    public static function getMiniWechat()
    {
        $appid = SysConfigModel::where('key','wx_app_id')->value('value');
        $secret = SysConfigModel::where('key','wx_app_secret')->value('value');

        if(!$appid || !$secret){
            throw new TokenException(['msg'=>'未配置appid,secret','errorCode'=>999]);
        }

        $config = [
            'app_id' => $appid,
            'secret' => $secret,

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

//            'log' => [
//                'level' => 'debug',
//                'file' => __DIR__.'/wechat.log',
//            ],
        ];
        $app = Factory::miniProgram($config);
        return $app;
    }

}
