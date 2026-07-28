<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 9:03
 */

namespace app\model;


use bases\BaseModel;

class User extends BaseModel
{
    /**
     * 获取所有用户信息
     * @return mixed
     */
    public function setCreateTimeAttr($value){
        return strtotime($value);
    }
    public static function getAllUser($page=1,$size=10,$search='')
    {
        $page = (int) (input('page', $page) ?: 1);
        $size = (int) (input('size', $size) ?: 10);
        $search = input('search', $search);
        if (is_string($search)) {
            $decoded = json_decode($search, true);
            $search = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($search)) {
            $search = [];
        }

        $obj = self::field('id,sender_name,province,mobile,city,county,is_ban,is_sender,is_writeoff,nickname,user_type,headpic,points,web_auth_id,create_time,name');

        if (!empty($search['id'])) {
            $obj->where('id', (int) $search['id']);
        }
        if (!empty($search['nickname'])) {
            $obj->where('nickname', 'like', '%' . trim($search['nickname']) . '%');
        }
        if (!empty($search['mobile'])) {
            $obj->where('mobile', 'like', '%' . trim($search['mobile']) . '%');
        }
        if (!empty($search['name'])) {
            $obj->where('name', 'like', '%' . trim($search['name']) . '%');
        }
        if (isset($search['user_type']) && $search['user_type'] !== '' && $search['user_type'] !== null) {
            $obj->where('user_type', (string) $search['user_type']);
        }
        if (isset($search['is_ban']) && $search['is_ban'] !== '' && $search['is_ban'] !== null) {
            $obj->where('is_ban', (int) $search['is_ban']);
        }
        if (!empty($search['province'])) {
            $obj->where('province', 'like', '%' . trim($search['province']) . '%');
        }
        if (!empty($search['city'])) {
            $obj->where('city', 'like', '%' . trim($search['city']) . '%');
        }
        if (!empty($search['county'])) {
            $obj->where('county', 'like', '%' . trim($search['county']) . '%');
        }
        if (!empty($search['date']) && is_array($search['date']) && count($search['date']) >= 2) {
            $start = (int) $search['date'][0];
            $end = (int) $search['date'][1];
            if ($start > 10000000000) {
                $start = (int) ($start / 1000);
            }
            if ($end > 10000000000) {
                $end = (int) ($end / 1000);
            }
            $obj->whereBetween('create_time', [$start, $end]);
        }

        $total = $obj->count();
        $data = $obj->limit(($page-1)*$size,$size)
                ->order('id desc')
                ->select();

        $res=['data'=>$data,'total'=>$total];
        return app('json')->success($res);
     }
    /**
     * 改变用户禁用状态
     * @return mixed
     */
    public static function revertBan($id,$state)
    {
        $res = self::where("id",$id)->update(['is_ban'=>$state]);
        return app('json')->success($res);
    }
    /**
     * 改变用户核销员状态
     * @return mixed
     */
    public static function revertWriteoff($id,$state)
    {
        $res = self::where("id",$id)->update(['is_writeoff'=>$state]);
        return app('json')->success($res);
    }
    /**
 * 改变用户核销员状态
 * @return mixed
 */
    public static function revertSender($id,$state)
    {
        $res = self::where("id",$id)->update(['is_sender'=>$state]);
        return app('json')->success($res);
    }
    public function vip()
    {
        return $this->belongsTo('FxAgent', 'id', 'user_id');
    }
//    public function getNicknameAttr($v)
//    {
//        return base64_decode($v);
//    }
}