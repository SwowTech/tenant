<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

//询价
Route::group('ask_price', function () {
    //公共
    Route::group('user', function () {
        Route::resource('ask_price_manage', 'cms.AskPriceManage');//用户询价
    });

    //管理员
    Route::group('admin', function () {
        Route::resource('ask_price_manage', 'cms.AskPriceManage');//用户询价
        Route::get('toggle_handle', 'cms.AskPriceManage/toggleHandle');//标记为已处理
    })->middleware('CheckCms');
});

Route::group('batch', function () {
    //用户
    Route::group('', function () {
        Route::get('get_user_sended_list', 'cms.BatchManage/getUserSentList');//获得某个商品的所有批次
    });
});
Route::group('xcx', function () {
    Route::get('/wechat_pay', 'user.User/wechatPay'); //用户支付
    Route::group('user', function () {
        Route::get('/get_info', 'user.User/getInfo'); //获取用户基础信息
        Route::post('/update_user_info', 'user.User/updateUserInfo'); //更新用户信息
        Route::post('/save_position', 'user.User/savePosition'); //保存位置
        Route::post('/get_phone', 'user.User/getPhone'); //获取电话
        Route::post('/user_apply', 'user.User/userApply'); //用户申请
    })->middleware('CheckMCMS');
});

Route::get('captcha_pro', 'common/Common/captchaPro');
Route::group(function () {
    Route::any('get_config_value', 'cms.System/getConfigValue');   //获取配置信息
    Route::any('get_config', 'cms.System/getConfig');   //获取商城配置信息
});
//授权注册登录
Route::group('auth', function () {
    Route::get('wxcode_url', 'auth.Auth/wxcodeUrl');   //请求公众号code
    Route::get('get_app_type', 'auth.Auth/getAppType');   //请求公众号code
    Route::post('get_xcx_token', 'auth.Auth/getXcxToken');   //小程序获取用户token
    Route::post('get_app_token', 'auth.Auth/getAppToken');   //app获取用户token
    Route::post('token_verify', 'auth.Auth/verifyToken');   //验证用户token
    Route::get('gzh_token', 'auth.Auth/gzhToken');   //异步接收公众号code,获取openid，返回token
    Route::post('xcx_upinfo', 'auth.Auth/updateXcxUserInfo');   //更新用户昵称、头像
    Route::get('/get_wx_jskey', 'auth.Auth/getWxJsKey');   //公众号js调用
    Route::post('/register', 'user.User/register');   //用户注册
    Route::post('/login', 'auth.Html/login');   //用户登录
});
