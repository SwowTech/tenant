<?php

namespace app\validate;


use bases\BaseValidate;

class CountValidate extends BaseValidate
{
    protected $rule = [
        'page' => 'require|isNotEmpty',  //分类id
        'size' => 'require|isNotEmpty',  //分类id
        'dispType' => 'require|isNotEmpty',  //分类id
    ];
    protected $message  = [
    ];
}
