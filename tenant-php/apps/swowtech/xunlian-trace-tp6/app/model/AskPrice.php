<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/14 0014
 * Time: 13:55
 */

namespace app\model;

use bases\BaseModel;

class AskPrice extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    public function user()
    {
        return $this->hasOne('User', 'id', 'uid');
    }

}
