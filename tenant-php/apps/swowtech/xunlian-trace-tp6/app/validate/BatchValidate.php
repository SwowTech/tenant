<?php

namespace app\validate;


use bases\BaseValidate;

class BatchValidate extends BaseValidate
{
    protected $rule = [
        'goods_id' => 'require|isPositiveInteger',
        'page' => 'require|isNotEmpty',  //分类id
        'size' => 'require|isNotEmpty',  //分类id
    ];
    protected $message  =   [

    ];
}
