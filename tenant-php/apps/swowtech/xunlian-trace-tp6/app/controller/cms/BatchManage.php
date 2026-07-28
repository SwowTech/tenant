<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 10:06
 */

namespace app\controller\cms;


use app\admin\controller\Count;
use app\model\Goods as GoodsModel;
use app\model\Batch as BatchModel;
use app\validate\BatchAddValidate ;
use app\validate\BatchEditValidate;
use app\validate\BatchValidate;
use app\validate\CountValidate;
use bases\BaseController;
use app\services\CodeService;
use app\validate\batch\BatchDeleteValidate;
use app\validate\batch\BatchSendValidate;



class BatchManage extends BaseController
{

    /**
     * cms 获得某商品批次
     */
    public function getResult($goods_id,$code)
    {
        $validate = new BatchValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return BatchModel::getResult($post);
    }
    /**
     * cms 获得某商品批次
     */
    public function getBatchesByGoods($goods_id,$search='',$size=10,$page=1)
    {
        $post = input('post.');
        return BatchModel::getBatchesByGoods($post);
    }
    /**
     * 小程序发货列表
     */
    public function getUserSentList($type='all',$page=1, $size=10)
    {
        if ($type != 'all' && $type != 'statistics') {
            return app('json')->fail();
        }
        $data = BatchModel::getUserSendedList($type, $page, $size);
        return app('json')->success($data);
    }
    /**
     * cms 获得某商品批次
     */
    public function getBatchesSent()
    {
        $validate = new BatchSendValidate();
        $validate->goCheck();
        return BatchModel::getBatchesSent(input('post.'));
    }
    /**
     * cms 增加某商品批次
     */
    public function addBatch($goods_id,$batch)
    {
        $validate = new BatchAddValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return BatchModel::addBatch($post);
    }
    /**
     * cms 查询某商品批次
     */
    public function getBatch($goods_id='',$search='',$page=1,$size=10)
    {
        return BatchModel::getBatch($goods_id,$search,$page,$size);
    }
    /**
     * cms 删除某商品批次
     */
    public function deleteBatch($id)
    {
        $validate = new BatchDeleteValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return BatchModel::deleteBatch($post);
    }
    /**
     * cms 增加某商品批次
     */
    public function editBatch($id,$batch)
    {
        $validate = new BatchEditValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return BatchModel::editBatch($post);
    }
}