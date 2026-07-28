<?php

namespace app\validate\batch;


use bases\BaseValidate;

class BatchSendValidate extends BaseValidate
{
    protected $rule = [
        'page' => 'require|isNotEmpty',  //分类id
        'size' => 'require|isNotEmpty',  //分类id
    ];
}
