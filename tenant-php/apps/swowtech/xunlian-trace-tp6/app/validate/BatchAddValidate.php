<?php

namespace app\validate;


use bases\BaseValidate;

class BatchAddValidate extends BaseValidate
{
    protected $rule = [
        'goods_id' => 'require|isPositiveInteger',
        'batch' => 'require|isBatch',
//        'time' => 'require',
//        'team' => 'require',
    ];
    protected $message  = [
        'batch' => '批次号不能为空'
    ];
}
