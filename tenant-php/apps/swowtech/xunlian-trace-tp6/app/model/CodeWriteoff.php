<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 13:00
 */

namespace app\model;


use bases\BaseModel;

use app\model\Goods as GoodsModel;
use app\model\Admin as AdminModel;
use app\model\User as UserModel;

class CodeWriteoff extends BaseModel
{
    /**
     * 获取所有核销结果
     * @return mixed
     */
    public static function getAllList($page=1,$size=10,$uid='',$aid='',$search_code = null)
    {
        $obj = self::with('code_item.batch.goods')->field('id,code,create_time,uid,mcid');

        if ($uid) {
            $obj->where('uid', $uid);
        }else{
            if ($aid) {
                $obj->where('mcid', $aid)->where('uid', 0);
            }
        }

        if(isset($search_code['key']) && $search_code['key']){
            $obj->where('code', 'like',  '%'.$search_code['key'].'%');
        }
        if(isset($search_code['date']) && $search_code['date']){

            $start=$search_code['date'][0]/1000;
            $end=$search_code['date'][1]/1000;

            $obj->whereBetweenTime('create_time',$start,$end);
        }
        $total = $obj->count();

        $data = $obj->limit(($page - 1) * $size, $size)
            ->order('create_time desc')
            ->select();

        foreach ($data as $k => &$v){
            $v['user'] = UserModel::where('id',$v['uid'])->field('id,nickname,headpic')->find();
            $v['admin'] = AdminModel::where('id',$v['mcid'])->field('id,username')->find();
            $v['goods_name'] = GoodsModel::where('goods_id',$v['goods_id'])->value('goods_name');
        }

        $res = ['data' => $data, 'total' => $total];
        return $res;
    }
    public function user()
    {
        return $this->hasOne('User', 'id', 'uid');
    }
    public function admin()
    {
        return $this->hasOne('Admin', 'id', 'mcid');
    }
    //关联商品
    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'goods_id');
    }
    public function codeItem()
    {
        return $this->belongsTo('Code', 'code', 'code');
    }

}