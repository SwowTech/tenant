<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/22 0022
 * Time: 11:02
 */

namespace app\model;


use bases\BaseModel;

class OrderLog extends BaseModel
{
    protected $insert=['ip','operator'];
    protected $hidden=['id','order_id'];

    protected function setIpAttr()
    {
        return request()->ip();
    }
    protected function setOperatorAttr()
    {
        try{
            $name = TokenService::getCurrentTokenVar('username');
        }catch (\Exception $e){

        }
        if(!empty($name)){
            return $name;
        }else {
            return '其他';
        }

    }
}