<?php

namespace app\validate\batch;


use bases\BaseValidate;

class BatchDeleteValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
    ];
}
