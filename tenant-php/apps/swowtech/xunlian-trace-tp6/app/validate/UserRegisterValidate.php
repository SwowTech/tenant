<?php

namespace app\validate;


use bases\BaseValidate;

class UserRegisterValidate extends BaseValidate
{

    protected $rule = [
        'mobile' => 'require|mobile',
        'pwd' => 'require|isNotEmpty',
        're_pwd' => 'require|confirm:pwd',
    ];
    protected $message = [
        'mobile' => '请输入手机号',
        'pwd' => '请输入密码',
        're_pwd' => '两次输入密码不一致',
    ];

    protected $scene = [
        'login'  =>  ['mobile','pwd']
    ];
}
