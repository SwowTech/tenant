<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 9:06
 */

namespace app\model;


use app\api\service\TuiService;
use app\services\TokenService;
use bases\BaseModel;
use exceptions\BaseException;
use exceptions\OrderException;
use think\facade\Db;
//use think\model\concern\SoftDelete;

class Order extends BaseModel
{
   // use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $hidden = ['delete_time'];

    /**
     * 用户删除订单
     * @param $uid
     * @param $id
     * @return int
     */
    public static function deleteOrder($id,$uid){
        $order= self::where(['order_id'=>$id])->find();
        if ($order['user_id'] == $uid && $order['payment_state'] == 0) {
            $order->delete(config('setting.soft_del'));   //这里是软删除
        } else {
            return app('json')->fail();
        }
        return app('json')->success();
    }

    /**
     * 用户提交评价
     * @param $uid
     * @param $post
     * @return mixed
     * @throws
     */
    public static function setPj($uid,$post){
        $where['user_id'] = $uid;
        $where['order_id'] = $post['id'];
        $data=[];
        $order = self::where($where)->where(['state'=>1])->find();
        if (!$order) {
            return app('json')->fail('评价出现错误');
        }
        $orderGoods=new OrderGoods();
        $order_id = $order['order_id'];
        $goods_ids=$orderGoods->where('order_id',$order_id)->column('goods_id');
        foreach ($post['json'] as $k=>$v){
            if(in_array($v['goods_id'],$goods_ids)) {
                $data[$k]['user_id'] = $where['user_id'];
                $data[$k]['rate'] = $v['rate'];
                $data[$k]['content'] = $v['content'];
                $data[$k]['order_id'] = $order_id;
                $data[$k]['goods_id'] = $v['goods_id'];
                $data[$k]['imgs'] = $v['imgs'];
                $data[$k]['create_time'] = time();
            }
        }
        $rate=new Rate();

        Db::startTrans();
        try {
            OrderGoods::where(['order_id'=>$post['id'],'user_id'=>$uid])->update(['state' => 2]);
            $order->save(['state' => 2]);
            $rate->saveAll($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new OrderException(['msg' => '更新失败']);
        }
        return app('json')->success();
    }

    /**
     * 确认收货
     * @param $id
     * @param $uid
     * @return mixed
     * @throws
     */
    public static function receive($id,$uid){
        $where['shipment_state'] = 1;
        $where['payment_state'] = 1;
        $where['state'] = 0;
        $where['order_id'] = $id;
        $where['user_id'] = $uid;
        $order = self::where($where)->find();
        if (!$order) {
            return app('json')->fail('订单状态有误');
        }
        Db::startTrans();
        try{
            $res = $order->save(['state' => 1,'shipment_state'=>2]);
            OrderGoods::where(['order_id'=>$id,'user_id'=>$uid])->update(['state' => 1]);
            event('EndOrder',$order);//订单完成监听
            event("AddGoodsFxRecord",[$id,$uid]);//分销订阅
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new OrderException(['msg'=>$e->getMessage()]);
        }
        if($res){
            return app('json')->success();
        }else{
            return app('json')->fail();
        }
    }

    /**
     * 申请退款
     * @param $uid
     * @param $post
     * @return mixed
     * @throws
     */
    public static function applyRefund($uid,$post)
    {
        $ck = Tui::where(['order_id' => $post['id'], 'status'=>['>',0]])->find();
        if ($ck) {
            return app('json')->fail('已存在正在处理的退款申请');
        }
        $user = User::find($uid);
        $where['user_id'] = $uid;
        $where['order_id'] = $post['id'];
        $where['payment_state'] = 1;
        $where['shipment_state'] = 0;
        $where['state'] = 0;
        $order = self::where($where)->find();
        if (!$order) {
            return app('json')->fail('订单状态有误');
        }
        $data['order_id'] = $order['order_id'];
        $data['nickname'] = $user['nickname'];
        $data['order_num'] = $order['order_num'];
        $data['money'] = $order['order_money'];
        $data['because'] = $post['radio'] ?: "";
        $data['message'] = $post['content'] ?: "";
        Db::startTrans();
        try {
            $res = $order->save(['state' => -1]);
            OrderGoods::where(['order_id'=>$post['id'],'user_id'=>$uid])->update(['state' => -1]);
            if (!$res) {
                return app('json')->fail('订单状态有误');
            }
            $tui = Tui::create($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new OrderException(['msg' => '更新失败']);
        }
        if($tui){
            return app('json')->success();
        }else{
            return app('json')->fail();
        }
    }

    /**
     * 修改发货状态
     * @param $param
     * @return bool
     * @throws
     */
    public static function editCourier($param){
        $pay=self::where('order_id',$param['order_id'])->value('payment_state');
        if($pay!=1) {
            return app('json')->fail('未支付的订单无法发货');
        }
        Db::startTrans();
        try {
            $courier['courier'] = $param['courier'];
            $courier['courier_num']=$param['courier_num'];
            $courier['shipment_state'] = 1;
            self::where('order_id',$param['order_id'])->Update($courier);
            $save['order_id'] = $param['order_id'];
            $save['type_name']='录入快递单号';
            $save['content']=$param['courier'].'，'.$param['courier_num'];
            OrderLog::create($save);
            Db::commit();
            return app('json')->success();
        }catch (\Exception $e) {
            Db::rollback();// 回滚事务
            throw new OrderException(['msg'=>'快递信息录入失败']);
        }
    }

    /**
     * 添加订单备注信息
     * @param $param
     * @return mixed
     * @throws
    */
    public static function up_remark_model($param)
    {
        Db::startTrans();
        try {
            self::where('order_id', $param['order_id'])->update(['remark_one' => $param['remark']]);
            $save['order_id'] = $param['order_id'];
            $save['type_name']='添加备注';
            $save['content']=$param['remark'];
            OrderLog::create($save);
            Db::commit();
            return app('json')->success();
//            return true;
        }catch (\Exception $e) {
            Db::rollback();// 回滚事务
            throw new OrderException(['msg'=>'备注信息录入失败']);
        }
    }

    /**
     * 修改订单价格
     * @param $param
     * @return mixed
     * @throws
     */
    public static function edit_price_model($param)
    {
        Db::startTrans();
        try {
            $order=self::where('order_id', $param['order_id'])->find();
            $order['edit_money']= $param['price'];
            $order['order_money']=$order['order_money']-$order['edit_money'];
            if($order['order_money']<=0){
              return app('json')->fail('价格错误');
            }
            $order->save();
            $save['order_id'] = $param['order_id'];
            $save['type_name']='修改订单金额';
            $save['content']=$param['price'];
            OrderLog::create($save);
            Db::commit();
            return app('json')->success();
        }catch (\Exception $e) {
            Db::rollback();// 回滚事务
            throw new OrderException(['msg'=>$e->getMessage()]);
        }
    }

    /**
     * 微信退款
     * @param $id
     * @return mixed
     */
    public static function TuiMoney($id){
        $order_num = self::where(['order_id' => $id])->value('order_num');
        $tui = Tui::where(['status' => 0, 'order_num' => $order_num])->find();
        if (!$tui) {
            return app('json')->fail('退款订单异常');
        }
        $tui = new TuiService($id);
        return $tui->pay();
    }

    /**
     * 退款驳回
     * @param $id
     * @param $msg
     * @return mixed
     * @throws
     */
    public static function rejectRefund($id, $msg)
    {
        //这里的id是tui的id
        $tui = Tui::find($id);
        if(!$tui){
            return app('json')->fail('id错误');
        }
        $order_id = $tui['order_id'];
        $aid = TokenService::getCurrentAid();
        $data['aid'] = $aid;
        $data['remark'] = $msg;
        $data['status'] = -1;
        Db::startTrans();
        try {
            $tui->save($data);
            $res = self::where(['order_id' => $order_id, 'state' => -1])->update(['state' => 0]);
            Db::commit();
            if (!$res) {
                return app('json')->fail();
            }
            return app('json')->success();
//            return $res ? 1 : 0;
        } catch (Exception $e) {
            Db::rollback();
            throw new OrderException(['msg' => $e->getMessage()]);
        }
    }

    /**
     * 关闭订单
     */
    public static function closeOrder(){
        $where['state']=0;
        $where['payment_state']=0;
        $time=time()-30*60;
        self::where($where)->whereTime('create_time','<',$time)->update(['state'=>-3]);
    }

    /**
     * 获取订单指定字段
     * @param $id
     * @param $field
     * @return mixed
     */
    public static function getOrderAttr($id,$field)
    {
        $value = self::where('order_id',$id)->value($field);
        if(!$value){
            throw new BaseException(['获取字段失败']);
        }
        return $value;
    }


    //关联规格模型
    public function sku()
    {
        return $this->hasMany('GoodsSku','goods_id','goods_id');
    }
    public function users()
    {
        return $this->belongsTo('User','user_id','id');
    }
    //关联订单商品模型
    public function ordergoods()
    {
        return $this->hasMany('OrderGoods','order_id','order_id');
    }
    //关联模型
    public function rate()
    {
        return $this->hasMany('Rate','order_id','order_id');
    }
    //关联图片
    public function imgs()
    {
        return $this->belongsTo('Image','goods_picture','id')->field('id,url');
    }

}