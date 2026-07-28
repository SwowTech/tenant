<?php

namespace app\validate;


use bases\BaseValidate;

class BatchEditValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'batch' => 'require|isBatch',
    ];
    protected $message  = [
        'batch' => '批次不能为空'
    ];
}
