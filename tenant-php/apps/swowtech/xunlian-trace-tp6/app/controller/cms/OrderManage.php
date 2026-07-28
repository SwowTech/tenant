<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 10:06
 */

namespace app\controller\cms;


use app\model\Order as OrderModel;
use app\model\Tui as TuiModel;
use app\validate\IDPostiveInt;
use bases\BaseController;
use services\QyFactory;
use think\facade\Request;

class OrderManage extends BaseController
{
    /**
     * 代发货订单提醒
     * @return mixed
     */
    public function checkDrive()
    {
        $res = OrderModel::where(['state'=>0,'payment_state'=>1,'shipment_state'=>0])->count();
        return app('json')->success($res);
    }


    /**
     * cms删除订单
     * @param string $id
     * @return int
     */
    public function deleteOrder($id = '')
    {
        (new IDPostiveInt)->goCheck();
        $result = OrderModel::where('order_id', $id)->find(); //这里是软删除
        if(!$result){
            return app('json')->fail();
        }
        if (!$result->delete(config('setting.soft_del'))) {
            return app('json')->fail();
        }
        return app('json')->success();
//        return $result?1:0;
    }

    /**
     * CMS获取所有订单简要
     * @return string
     */
    public function getOrderAll()
    {
        $key = $this->request->param('keywords') ?: '';
        $order = (new QyFactory())->instance('CmsService');
        $order->set_param($key);
        $data=$order->get_order_list();
        return app('json')->success($data);
    }

    /**
     * 获取指定订单详细--CMS
     * @param string $id
     * @return mixed
     */
    public function getOrderOne($id = '')
    {
        (new IDPostiveInt())->goCheck();
        $order = (new QyFactory())->instance('CmsService');
        $data=$order->get_order_detail($id);
        return app('json')->success($data);
    }


    /**
     * 更新订单配送信息
     * @return bool
     */
    public function editCourier()
    {
        $rule = [
            'courier' => 'require',
            'courier_num' => 'require|min:5',
            'order_id' => 'require|number',
        ];
        $param = Request::param();
        $this->validate($param, $rule);
        return OrderModel::editCourier($param);
    }

    /**
     * 添加订单备注信息
     * @return mixed
     */
    public function editRemark()
    {
        $rule = [
            'remark' => 'require',
            'order_id' => 'require|number',
        ];
        $param = Request::param();
        $this->validate($param, $rule,[],true);
        return OrderModel::up_remark_model($param);
    }

    /**
     * cms 获取所有退款信息
     * @return mixed
     */
    public function getTuiAll()
    {
        $res = TuiModel::select();
        foreach ($res as $k => $v) {
            $res[$k]['money'] = $v['money'] / 100;
        }
        return app('json')->success($res);
    }

    /**
     * 退款申请驳回
     * @param $id
     * @param $msg
     * @return mixed
     */
    public function rejectRefund($id, $msg)
    {
        return OrderModel::rejectRefund($id,$msg);
    }

}