<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 10:37
 */

namespace app\controller\cms;

use \app\model\AskPrice as AskPriceModel;
use app\validate\IDPostiveInt;
use bases\BaseController;
use services\QyFactory;
use app\services\TokenService;


class AskPriceManage extends BaseController
{
    /**
     * cms 新增分类
     * @param $form
     * @return int
     */
    public function index($page=1,$size=10,$keyword='')
    {
        $qr = AskPriceModel::order('id','desc');

        $where = [];
        if($keyword){
            $where[] = ['name' , 'like','%'.$keyword.'%'];
        }

        $data['total'] = $qr->where($where)->count();

        $data['data'] = $qr
            ->limit(($page-1)*$size,$size)
            ->with(['user'=> function($query){
                $query->field('id,nickname,headpic');
            }])
            ->where($where)
            ->select();
        return app('json')->success($data);
    }
    //设置已处理
    public function toggleHandle($id,$handle){

        $res = AskPriceModel::where('id',$id)->update(['handle' =>  $handle=='true'?1:0]);
        if($res){
            return app('json')->success();
        }else{
            return app('json')->fail();
        }

    }

    /**
     * cms 新增分类
     * @param $form
     * @return int
     */
    public function read()
    {
        $data = AskPriceModel::where('');
        return app('json')->success($data);
    }
    /**
     * cms更新分类
     * @param $form
     * @return int|\think\response\Json
     */
    public function save($form)
    {
        $rule=[
            'name'=> 'require|min:2',
            'content' => 'require|min:2',
            'phone' => 'require|min:2',
        ];

        $this->validate($form,$rule);
        $form['create_time'] = time();
        $form['uid'] = TokenService::getCurrentUid();

        $res = AskPriceModel::insert($form);
        if($res){
            return app('json')->success();
        }else{
            return app('json')->fail();
        }

    }
    public function update($form)
    {
        return AskPriceModel::update($form);
    }
    /**
     * cms 删除分类
     * @param $id
     * @return \think\Collection|void
     */
    public function delete($id)
    {
        if(is_numeric($id)){
            return AskPriceModel::destroy($id);
        }
    }


    /**
     * cms 获取所有分类并排好序，包括隐藏
     * @return \think\response\Json
     */
    public function getCateSort()
    {
        $article=(new QyFactory())->instance('CmsService');
        $data=$article->get_category_list();
        $data = subTree($data);
        return app('json')->success($data);
    }

    /**
     * 更新分类排序
     * @return int
     */
    public function setSort()
    {
        $arr=input('post.');
        return AskPriceModel::setSort($arr);
    }

}
