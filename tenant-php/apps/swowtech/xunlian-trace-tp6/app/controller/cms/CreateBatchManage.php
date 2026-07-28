<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 10:06
 */

namespace app\controller\cms;


use app\model\CreateBatch;
use app\model\Goods as GoodsModel;
use app\validate\ProductValidate;
use bases\BaseController;


class CreateBatchManage extends BaseController
{

    /**
     *
     */
    public function addCreateBatch($batch)
    {
        if(!$batch['name']){
            return app('json')->fail('名称不能为空');
        }
        return CreateBatch::addBatch($batch);
    }
    public function deleteCreateBatch($id)
    {
        if(!$id){
            return app('json')->fail('名称不能为空');
        }
        return CreateBatch::deleteCreateBatch($id);
    }
    public function getBatchList($page=1,$size=10,$search='')
    {
        return CreateBatch::getBatchList($page,$size,$search);
    }
    public function editBatch($id,$batch)
    {
        return CreateBatch::editBatch($id,$batch);
    }
    public function getCodesByCreateBatchId($id,$page=1,$size=10,$search='',$type = null)
    {
        return CreateBatch::getCodesByCreateBatchId($id,$page,$size,$search,$type);
    }
    public function codeAssign($operation,$start_id,$end_id,$batch_id='')
    {
        return CreateBatch::codeAssign($operation,$start_id,$end_id,$batch_id);
    }
    public function codeAssignProgress()
    {
        return CreateBatch::codeAssignProgress();
    }
}
