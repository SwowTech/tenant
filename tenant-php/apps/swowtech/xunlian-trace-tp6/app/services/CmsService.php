<?php

namespace app\services;

use app\model\Coupon as CouponModel;
use app\model\Article as ArticleModel;
use app\model\BannerItem as BannerItemModel;
use app\model\Category as CategoryModel;
use app\model\Order as OrderModel;
use app\model\OrderLog;

class CmsService
{
    private $param;

    public function set_param($param)
    {
        $this->param = $param;
    }

    //文章列表
    public function get_article_list($key_word = '', $page = 1, $size = 10)
    {
        // TODO: Implement get_article_list() method.
        $obj = ArticleModel::order('id', 'desc');
        if ($key_word) {
            $obj->where('title', 'like', '%' . $key_word . '%');
        }
        $res = $obj->paginate($size);
        return $res;
    }



    //分类列表
    public function get_category_list($page,$size,$search)
    {
        $obj = CategoryModel::with('imgs')->order(['sort'=>'desc','id']);
        // axios GET 会把对象序列化成 JSON 字符串，如 {"name":""}
        if (is_string($search) && $search !== '') {
            $decoded = json_decode($search, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $search = $decoded;
            }
        }
        if ($search) {
            if (is_array($search)) {
                if (!empty($search['name'])) {
                    $obj->where('name', 'like', '%' . trim($search['name']) . '%');
                }
                if (isset($search['is_visible']) && $search['is_visible'] !== '' && $search['is_visible'] !== null) {
                    $obj->where('is_visible', (int) $search['is_visible']);
                }
            } else {
                $obj->where('name', 'like', '%' . trim((string) $search) . '%');
            }
        }
        $data = $obj->paginate($size, false, ['page' => $page]);
        return $data;
    }

    //优惠券列表
    public function get_coupon_list()
    {
        $coupon = CouponModel::select();
        return $coupon;
    }

    //订单列表
    public function get_order_list()
    {
        $key=$this->param;
        if (isset($key) and !empty($key)) {
            $key = trim($key);
            $data = OrderModel::with(['ordergoods.imgs', 'users' => function ($query) {
                $query->field('id,nickname,headpic');
            }])->where('order_num', 'like', '%' . $key . '%')
                ->order('create_time desc')
                ->field('order_id,order_num,user_id,state,payment_state,shipment_state,delete_time,update_time,pay_time,shipping_money,order_money,user_ip,message', true)
                ->select();
        } else {
            $data = OrderModel::with(['ordergoods.imgs', 'users' => function ($query) {
                $query->field('id,nickname,headpic');
            }])
                ->order('create_time desc')->field('order_id,order_num,user_id,state,payment_state,shipment_state,delete_time,update_time,pay_time,shipping_money,order_money,user_ip,message', true)
                ->select();
        }
        return $data;
    }

    //订单详情
    public function get_order_detail($id)
    {
        $data['order'] = OrderModel::with(['ordergoods.imgs','imgs','users' => function ($query) {
                $query->field('id,nickname,headpic');
            }])->where(['order_id' => $id])->find();
        $data['log'] = OrderLog::where(['order_id' => $id])->order('create_time desc')->select();
        return $data;
    }

}