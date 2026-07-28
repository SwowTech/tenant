<?php

namespace app\services;

use app\model\Order;
use WxPay\WxPayNotify;

class WxNotifyService extends WxPayNotify
{
    //异步接收微信回调，更新订单状态
    public function NotifyProcess($data, &$msg)
    {
        if ($data['result_code'] == 'SUCCESS') {

            $orderNo = $data['out_trade_no'];
            $notify=new NotifyService();
            return $notify->NotifyEditOrder($orderNo);
        } else {
            return true;
        }
    }


}