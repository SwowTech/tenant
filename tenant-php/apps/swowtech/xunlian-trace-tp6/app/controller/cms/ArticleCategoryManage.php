<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 10:37
 */

namespace app\controller\cms;

use bases\BaseController;
use services\QyFactory;
use app\model\ArticleCategory as ArticleCategoryModel;

class ArticleCategoryManage extends BaseController
{
    /**
     * 新增分类
     * @param $form
     * @return int
     */
    public function index($page = 1,$size=10,$search = null)
    {
        $search = json_decode($search,true);
        $category = ArticleCategoryModel::getAll($page,$size,$search);
        $data = subTree($category);
        return app('json')->success($data);
    }

    /**
     * 分类详情
     * @param $form
     * @return int
     */
    public function read()
    {
        $category = ArticleCategoryModel::getAll();
        $data = subTree($category);
        return app('json')->success($data);
    }

    /**
     * 更新分类
     * @param $form
     * @return int|\think\response\Json
     */
    public function save($form)
    {
        $rule = [
            'name' => 'require|min:2',
        ];
        $message = [
            'name' => '名称不能为空',
        ];
        $this->validate($form, $rule, $message);
        if (isset($form['id']) && $form['id']) {
            ArticleCategoryModel::editCategory($form);
        } else {
            ArticleCategoryModel::addCategory($form);
        }
        return app('json')->success();
    }
    public function update($form)
    {
        $rule = [
            'name' => 'require|min:2',
        ];
        $this->validate($form, $rule);
        ArticleCategoryModel::editCategory($form);
        return app('json')->success();
    }

    /**
     * 删除分类
     * @param $id
     * @return int
     */
    public function delete($id)
    {
        if(is_numeric($id)){
            ArticleCategoryModel::deleteCategory($id);
        }
        return app('json')->success();
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
        $arr = input('post.');
        ArticleCategoryModel::setSort($arr);
        return app('json')->success();
    }

}