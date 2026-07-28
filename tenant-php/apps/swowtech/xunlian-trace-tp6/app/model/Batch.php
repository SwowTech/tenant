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
use app\model\Batch as BatchModel;
use app\model\Code as CodeModel;
use app\services\TokenService;
use exceptions\TipException;

class Batch extends BaseModel
{
    //use SoftDelete;
    protected $deleteTime = 'delete_time';

    public static $status=[
            0=>'正常',
            1=>'已验证',
            2=>'拉黑'
        ];

    public function goodsSku()
    {
        return $this->hasOne('GoodsSku', 'goods_id', 'goods_id');
    }
    public function getStatusDescAttr($value)
    {
        if(array_key_exists($value,self::$status)){
            return self::$status[$value];
        }else{
            return '状态未知';
        }
    }
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function setCreateTimeAttr($value)
    {
        return strtotime($value);
    }
    public function getSendTimeAttr($value)
    {
       return date('Y-m-d H:i:s',$value);
    }
    public function setSendTimeAttr($value)
    {
        return strtotime($value);
    }
    public function getDiyContentAttr($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        // 兼容历史错误的双重 json_encode
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        return is_array($decoded) ? $decoded : [];
    }
    public function setDiyContentAttr($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 已是 JSON：若解码后仍是字符串，说明被双重编码，剥掉一层
                if (is_string($decoded)) {
                    return $decoded;
                }
                if (is_array($decoded)) {
                    return $value;
                }
            }
        }
        return json_encode($value ?: [], JSON_UNESCAPED_UNICODE);
    }

    //关联商品
    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'goods_id');
    }
    //关联商品
    public function total()
    {
        return self::count();
    }
    //手机端发货信息
    public static function getUserSendedList($type,$page=1,$size=10){
        $uid = TokenService::getCurrentUid();
        $total = 0;
        switch($type){
            case 'all':{

                $query = BatchModel::where('send_state',1)
                        ->where('sender_id',$uid);
                $total = $query->count();

                $data = $query
                    ->with(['goods' => function($query){
                        $query->field('goods_id,goods_name');
                    }])
                    ->order('send_time','desc')
                    ->limit(($page-1)*$size,$size)->select();

                break;
            }
            case 'statistics':{
                $data['total'] = BatchModel::where('sender_id',$uid)->where('send_state',1)->count();

                $today_start = strtotime(getTodayStart());
                $today_end = strtotime(getTodayEnd());

                $month_start = strtotime(getMonthStart());
                $month_end =   strtotime(getMonthEnd());

                $data['today'] = BatchModel::where('sender_id',$uid)->where('send_state',1)->whereBetweenTime('send_time',$today_start,$today_end)->count();
                $data['month'] = BatchModel::where('sender_id',$uid)->where('send_state',1)->whereBetweenTime('send_time',$month_start,$month_end)->count();
            }
        }

        $res['data'] = $data;
        $res['total'] = $total;

        return $res;

    }
    public static function getBatchesByGoods($post){
        $query = BatchModel::with(['goods' => function ($query) {
            $query->field('goods_id,goods_name');
        }])->order('id', 'desc');
        if (isset($post['goods_id']) && $post['goods_id']) {
            $batchs = $query->where('goods_id', $post['goods_id']);
        }
        if (isset($post['search']['date']) && $post['search']['date']) {
            $query = $query->where('create_time', '>=', $post['search']['date'][0] / 1000);
            $query = $query->where('create_time', '<=', $post['search']['date'][1] / 1000);
        }
        if (isset($post['search']['code']) && $post['search']['code']) {
            $query = $query->where('name', 'like', '%' . $post['search']['code'] . '%');
        }
        $res = $query->limit(($post['page'] - 1) * $post['size'], $post['size'])
            ->select();

        $total = $query->count();
        $data = [
            'total' => $total,
            'data' => $res
        ];

        return app('json')->success($data);

    }
    public static function getBatch($goods_id,$search,$page=1,$size=10){
        $query = Batch::order('id','desc');
        $data = [];
        if($goods_id){
            $data = BatchModel::where('id', $goods_id)->with(['goods'=> function($query1){
                $query1->field('goods_id,goods_name');
            }])->paginate($size);
        }else{
            if($search['id']){
                $data = $query->where('id', $search['id'])->with(['goods'=> function($query1){
                    $query1->field('goods_id,goods_name');
                }])->paginate($size);
            }
            if($search['name']){
                $data = $query->where('name','like', '%'.$search['name'].'%')->with(['goods'=> function($query1){
                    $query1->field('goods_id,goods_name');
                }])->paginate($size);
            }
        }
        return app('json')->success($data);
    }
    public static function getBatchesSent($post){
        $query = BatchModel::whereRaw('1=1');

        if(isset($post['search'])){
            if($post['search']['date']){
                $query = $query->where('create_time','>=',$post['search']['date'][0]/1000);
                $query = $query->where('create_time','<=',$post['search']['date'][1]/1000);
            }
            if($post['search']['batch_name']){
                $query = $query->where('name','like','%'.$post['search']['batch_name'].'%');
            }
            if($post['search']['fans_id']){
                $query = $query->where('sender_id',$post['search']['fans_id']);
            }
            if($post['search']['state']=='sended'){
                $query = $query->where('send_state',1);
            }
            if($post['search']['state']=='not_send'){
                $query = $query->where('send_state',0);
            }
            if($post['search']['fans_id']){
                $query = $query->where('sender_id',$post['search']['fans_id']);
            }
        }
        $total = $query->count();
        $res =  $query->limit(($post['page']-1)*$post['size'],$post['size'])->order('id desc')->select();
        foreach ($res as $k => $v){
            $v['code_count'] = CodeModel::where('batch_id',$v['id'])->count();
        }
        $data=[
            'total'=>$total,
            'data' => $res
        ];

        return app('json')->success($data);

    }
    //修改批次
    public static function editBatch($post){

        $id=$post['id'];
        $batch = $post['batch'] ?? [];
        unset($batch['code_count'], $batch['goods'], $batch['id']);

        if (isset($batch['create_time']) && $batch['create_time'] !== '' && !is_numeric($batch['create_time'])) {
            $batch['create_time'] = strtotime($batch['create_time']);
        }
        if (isset($batch['send_time']) && $batch['send_time'] !== '' && !is_numeric($batch['send_time'])) {
            $batch['send_time'] = strtotime($batch['send_time']);
        }
        // where()->update 不走修改器，这里只编码一次
        if (array_key_exists('diyContent', $batch)) {
            if (is_array($batch['diyContent'])) {
                $batch['diyContent'] = json_encode($batch['diyContent'], JSON_UNESCAPED_UNICODE);
            } elseif (is_string($batch['diyContent'])) {
                $decoded = json_decode($batch['diyContent'], true);
                if (is_string($decoded)) {
                    $batch['diyContent'] = $decoded;
                } elseif (!is_array($decoded)) {
                    $batch['diyContent'] = json_encode([], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $batch['diyContent'] = json_encode([], JSON_UNESCAPED_UNICODE);
            }
        }

        BatchModel::where('id', $id)->update($batch);

        return app('json')->success();

    }
    //新增批次
    public static function addBatch($post){

        $batch = $post['batch'] ?? [];
        unset($batch['id'], $batch['goods'], $batch['code_count'], $batch['batch_code_count_refresh']);
        $batch['goods_id'] = $post['goods_id'];
        if (!isset($batch['description']) || $batch['description'] === null) {
            $batch['description'] = '';
        }
        if (!isset($batch['mcid']) || $batch['mcid'] === null || $batch['mcid'] === '') {
            $batch['mcid'] = 1;
        }
        if (!isset($batch['diyContent']) || $batch['diyContent'] === null || $batch['diyContent'] === '') {
            $batch['diyContent'] = [];
        }
        // create 会走 setDiyContentAttr，不要提前 json_encode，否则双重编码
        if (empty($batch['create_time'])) {
            $batch['create_time'] = date('Y-m-d H:i:s');
        }
        BatchModel::create($batch);
        return app('json')->success();
    }
    //删除批次
    public static function deleteBatch($post){
        if(self::canDelete($post['id'])){
            $res = BatchModel::destroy($post['id']);
        }else{
            throw new TipException('请先删除该批次下的溯源码');
        }

        return app('json')->success();
    }
    //是否能删除此批次
    public static function canDelete($id){
        $count = CodeModel::where('batch_id',$id)->count();
        if($count>0){
            return false;
        }else{
            return true;
        }
    }
    //获取商品通过批次id
    public static function getProductByID($id){
        $goods_id = self::where('id',$id)->value('goods_id');
        $product = GoodsModel::where('goods_id',$goods_id)->find();
        return $product;
    }
}
