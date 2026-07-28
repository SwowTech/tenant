<?php

namespace app\validate;


use bases\BaseValidate;

class CodeValidate extends BaseValidate
{
    protected $rule = [
        'code' => 'require|isNotEmpty',  //分类id
        'page' => 'require|isNotEmpty',  //分类id
        'size' => 'require|isNotEmpty',  //分类id
    ];
    protected $message  = [
    ];
}
