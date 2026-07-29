<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 10:09
 */

namespace subscribes;

use app\model\Goods as GoodsModel;
use app\model\Order as OrderModel;
use app\services\DeliveryMessage;
use app\services\GzhDeliveryMessage;

/**
 * 订单事件
 * Class OrderSubscribes
 * @package subscribes
 */
class OrderSubscribes
{
    public function handle()
    {

    }

    /**
     * 检测库存
     * @param $event
     * @return int
     * @throws OrderException
     */
    public function onCheckStock($event)
    {
        $data = OrderModel::with('ordergoods')->where('order_id', $event)->find();
        GoodsModel::checkStock($data['order_goods']);
    }

    /**
     * 扣除库存
     * @param $event
     */
    public function onReduceStock($event)
    {
        $data=$event['order_goods'];
        GoodsModel::editStock($data);
    }

    /**
     * 公众号发送模板消息通知管理员
     * @param $event
     */
    public function onSendGzhDeliveryMessage($event)
    {

        list($data,$type,$ids)=$event;
        $message = new GzhDeliveryMessage();
         $message->sendDeliveryMessage($data,$type,$ids);  //公众号发送模板消息通知管理员
    }

    /**
     * 小程序发送模板消息通知用户
     * @param $event
     */
    public function onSendDeliveryMessage($event)
    {
        $message = new DeliveryMessage();
        $message->sendDeliveryMessage($event, '');//小程序发送模板消息通知用户
    }
}