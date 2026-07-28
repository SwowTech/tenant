<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 13:00
 */

namespace app\model;


use bases\BaseModel;
use exceptions\BaseException;
use exceptions\OrderException;
use exceptions\ProductException;
use exceptions\TipException;
use think\Exception;
use think\facade\Db;
//use think\model\concern\SoftDelete;
use app\model\CodeResult as CodeResultModel;
use app\model\Goods as GoodsModel;
use app\model\Code as CodeModel;
use app\model\CodeWriteoff as CodeWriteoffModel;
use app\services\TokenService;
use app\model\Batch as BatchModel;

class Code extends BaseModel
{
    //use SoftDelete;
    protected $deleteTime = 'delete_time';

    public static $status=[
            0=>'正常',
            1=>'已验证',
            2=>'拉黑'
        ];
    public function batch()
    {
        return $this->belongsTo('batch', 'batch_id', 'id');
    }
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
    public static function getGoodsIdByCode($certify_code)
    {
        $batch_id = CodeModel::where('code',$certify_code)->value('batch_id');
        return BatchModel::where('id',$batch_id)->value('goods_id');
    }

    //二维码核销
    public static function writeoff($certify_code,$uid,$mcid)
    {
        $codeModelInsertData=[
            'code' => $certify_code,
            'mcid' => $mcid,
            'create_time' => time(),
            'uid' => $uid,
        ];
        $res = CodeWriteoffModel::insert($codeModelInsertData);
        return $res;
    }
    //发货
    public static function sendGoods($send_params,$uid,$mcid)
    {
        $send_params['batch']['send_state'] = 1;
        $send_params['batch']['sender_id'] = $uid;
        $send_params['batch']['create_time'] = strtotime($send_params['batch']['create_time']);
        $send_params['batch']['send_time'] = strtotime($send_params['batch']['send_time']);
        $send_params['batch']['sender_id'] = $uid;
        unset($send_params['batch']['total']);
        $id = $send_params['batch']['id'];
        unset($send_params['batch']['id']);
        $res = BatchModel::update($send_params['batch'],['id' => $id]);
        return $res;
    }
    //关联商品
    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'goods_id');
    }
    //关联核销信息
    public function writeoffInfo()
    {
        return $this->belongsTo('CodeWriteoff', 'code', 'code');
    }
    //查询次数
    public function getCertifyCount()
    {
        $count = CodeResultModel::where('code',$this->code)->count();
        return $count;
    }
    public static function getResult($post){
        $res = CodeResultModel::where('code',$post['code'])
            ->limit(($post['page']-1)*$post['size'],$post['size'])
            ->select();
        $total = CodeResultModel::where('code',$post['code'])
            ->count();
        $data=[
            'total'=>$total,
            'data' => $res
        ];

        return app('json')->success($data);

    }
    public static function deleteByGoodsId($id){
        $batch_ids = Batch::where('goods_id', $id)->column('id');
        Code::whereIn('batch_id',$batch_ids)->delete();
    }

    /**
     * 校验指定 ID 的溯源码是否可删除（有扫码记录则禁止）
     * @param int|array $ids
     * @throws TipException
     */
    public static function assertCanDeleteByIds($ids)
    {
        $ids = array_values(array_filter((array)$ids, function ($id) {
            return $id !== '' && $id !== null;
        }));
        if (!$ids) {
            return;
        }
        if (self::whereIn('id', $ids)->where('scan_count', '>', 0)->find()) {
            throw new TipException('该溯源码已有用户扫码，不能删除');
        }
        $codes = self::whereIn('id', $ids)->column('code');
        if ($codes && CodeResultModel::whereIn('code', $codes)->find()) {
            throw new TipException('该溯源码已有用户扫码，不能删除');
        }
    }

    /**
     * 校验查询范围内的溯源码是否可删除（有扫码记录则禁止）
     * @param mixed $query
     * @throws TipException
     */
    public static function assertCanDeleteByQuery($query)
    {
        if ((clone $query)->where('scan_count', '>', 0)->find()) {
            throw new TipException('所选范围内存在已扫码的溯源码，不能删除');
        }
        $subSql = (clone $query)->field('code')->buildSql();
        if (CodeResultModel::whereRaw('code IN ' . $subSql)->find()) {
            throw new TipException('所选范围内存在已扫码的溯源码，不能删除');
        }
    }
}