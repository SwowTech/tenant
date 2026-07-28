<?php

namespace app\validate;


use bases\BaseValidate;

class ProductValidate extends BaseValidate
{
    protected $rule = [
        'goods_name' => 'require|isNotEmpty',
        'category_id' => 'require|isPositiveInteger',  //分类id
        'description' => 'max:200',
//        'market_price' => 'isNotEmpty', //市场价格
        'content' => 'min:0',  //商品详情
        'banner_imgs' => 'max:255', // //商品详情图
        'genuine_tip' => 'max:65535', //真品提示
        'counterfeit_tip' => 'max:65535', //伪品提示

    ];
    protected $message  =   [
        'goods_name' => '商品名不能为空',
        'category_id' => '商品分类不能为空',
        'market_price' => '市场价格不能为空',
    ];
}
