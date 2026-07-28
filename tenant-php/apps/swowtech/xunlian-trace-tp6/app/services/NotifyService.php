<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26 0026
 * Time: 16:32
 */

namespace app\services;


use app\model\Order as OrderModel;
use think\facade\Log;

class NotifyService
{
    //异步接收微信回调，更新订单状态
    public function NotifyEditOrder($orderNo)
    {
        $str = substr($orderNo, 0, 1);
        if ($str == 'P') {
            if (app('system')->getValue('is_pt') == 1) {
                return $this->upPtOrder($orderNo);
            } else {
                Log::error('拼团更新订单Notify：活动未开启');
                return false;
            }
        } else {
            return $this->upOrder($orderNo);
        }

    }

    //更新订单状态
    private function upOrder($orderNo)
    {
        try {
            //Lock方法是用于数据库的锁机制;
            $order = OrderModel::with(['ordergoods', 'users'])->where('order_num', $orderNo)->lock(true)->find();
            if ($order['payment_state'] == 0 && $order['state'] == 0) {

                OrderModel::where('order_id', $order['order_id'])->update(['payment_state' => 1, 'pay_time' => time()]);//更新订单状态为已支付
                event('ReduceStock', $order);//扣除库存
            }
        } catch (\Exception  $ex) {
            Log::error('更新订单Notify:' . $ex->getMessage());
            return false;
        }
        return true;
    }

    //更新订单状态
    private function upPtOrder($orderNo)
    {
        try {
            //Lock方法是用于数据库的锁机制;
            $order = PtOrderModel::where('order_num', $orderNo)->lock(true)->find();
            $item = PtItemModel::with('user')->where('id', $order['item_id'])->find();
            if ($order['payment_state'] == 0 && $order['state'] == 0) {
                PtOrderModel::where('id', $order['id'])->update(['payment_state' => 1, 'pay_time' => time()]);//更新订单状态为已支付
                if ($order['is_captain'] == 1 && $order['user_id'] == $item['user_id']) {
                    $data['state'] = 1;
                }
                $data['pay_user'] = $item['pay_user'] + 1;
                if ($data['pay_user'] == $item['user_num']) {
                    $data['state'] = 2;
                }
                $item->save($data);
                $res['order_goods'] = [$order];
                event('ReduceStock', $res);//扣除库存
            }
        } catch (\Exception  $ex) {
            Log::error('更新订单Notify:' . $ex->getMessage());
            return false;
        }
        return true;
    }
}