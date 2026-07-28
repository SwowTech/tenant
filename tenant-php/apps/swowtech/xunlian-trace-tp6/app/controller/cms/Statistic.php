<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/30 0030
 * Time: 14:18
 */

namespace app\controller\cms;


use app\model\Code as CodeModel;
use app\services\StatisticService;
use app\validate\CountValidate;
use bases\BaseController;

class Statistic extends BaseController
{
    /**
     * mcms订单统计
     * @return mixed
     */
    public function getOrderNum(){
        return StatisticService::getOrderNum();
    }

    /**
     * cms首页数据
     * @return mixed
     */
    public function getCmsIndexData(){
        return StatisticService::getCmsIndexData();
    }

    /**
     * cms图表统计
     * @return mixed
     */
    public function getTableData(){
        return StatisticService::getTableData();
    }
    /**
     * cms订单统计销售额
     * @return mixed
     */
    public function getMoneyData(){
        return StatisticService::getMoneyData();
    }
    /**
     * cms订单统计订单数据
     * @return mixed
     */
    public function getOrderData(){
        return StatisticService::getOrderData();
    }

    /**
     *省份预警数据
     * @return mixed
     */
    public function getWarningDataProvince($month=''){
        return StatisticService::getWarningDataProvince($month);
    }
    /**
     *商品查询数据
     * @return mixed
     */
    public function getProductVerifyData($month='',$dispType='genuine'){
        return StatisticService::getProductVerifyData($month,$dispType);
    }
    /**
     *地区查询数据
     * @return mixed
     */
    public function getAreaVerifyData($month='',$dispType='genuine'){
        return StatisticService::getAreaVerifyData($month,$dispType);
    }
    /**
     *单商品地区统计图表
     * @return mixed
     */
    public function getAreaByOneGoods($month='',$dispType='genuine',$id=''){
        return StatisticService::getAreaByOneGoods($month,$dispType,$id);
    }
    /**
     *地区单商品统计图表
     * @return mixed
     */
    public function getGoodsByArea($month='',$dispType='genuine',$area=''){
        return StatisticService::getGoodsByArea($month,$dispType,$area);
    }

   /**
    *地区统计表格
    * @return mixed
    */
    public function certifyAreaTop()
    {
        $validate = new CountValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return StatisticService::certifyAreaTop($post);
    }
    /**
     *商品统计表格
     * @return mixed
     */
    public function certifyGoodsTop()
    {
        $validate = new CountValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return StatisticService::certifyGoodsTop($post);
    }
    /**
     *核销统计
     * @return mixed
     */
    public function getWriteoffTable($month='')
    {
        return StatisticService::getWriteoffTable($month);
    }
    /**
     *核销统计表格
     * @return mixed
     */
    public function getWriteoffTotal()
    {
        return StatisticService::getWriteoffTotal();
    }
    /**
     *省份预警数据
     * @return mixed
     */
    public function getWarningDataProvinceTooltip($month=''){
        return StatisticService::getWarningDataProvinceTooltip($month);
    }
    /**
     * 待处理事项统计
     * @return mixed
     */
    public function remind(){
        return StatisticService::remind();
    }

    /**
     * 查询统计概览
     */
    public function getCertifyOverview($month = '', $dispType = 'total')
    {
        return StatisticService::getCertifyOverview($month, $dispType);
    }

}