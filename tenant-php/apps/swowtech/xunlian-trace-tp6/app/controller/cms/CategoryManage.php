<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 10:37
 */

namespace app\controller\cms;

use app\model\Category as CategoryModel;
use app\validate\IDPostiveInt;
use bases\BaseController;
use services\QyFactory;

class CategoryManage extends BaseController
{
    /**
     * cms 新增分类
     * @param $form
     * @return int
     */
    public function addCategory($form)
    {
        $rule=[
            'name'=> 'require|min:1',
//            'category_pic'=> 'require',
        ];
        $msg=[
            'name.require'=> '请输入分类名称',
            'name.min'=> '分类名称不能为空',
            'category_pic.require'=> '请上传分类图片',
        ];
        $this->validate($form,$rule,$msg);
        return CategoryModel::addCategory($form);
    }

    /**
     * cms更新分类
     * @param $form
     * @return int|\think\response\Json
     */
    public function editCategory($form)
    {
        $rule=[
            'id'=> 'require',
            'name'=> 'require|min:1',
//            'category_pic'=> 'require',
        ];
        $msg=[
            'name.require'=> '请输入分类名称',
            'name.min'=> '分类名称不能为空',
            'id.require'=> '分类ID不能为空',
            'category_pic.require'=> '请上传分类图片',
        ];
        $this->validate($form,$rule,$msg);
        return CategoryModel::editCategory($form);
    }

    /**
     * cms 删除分类
     * @param $id
     * @return \think\Collection|void
     */
    public function deleteCategory($id)
    {
        (new IDPostiveInt())->goCheck();
        return CategoryModel::deleteCategory($id);
    }


    /**
     * cms 获取所有分类并排好序，包括隐藏
     * @return \think\response\Json
     */
    public function getCateSort($page=1,$size=999,$search=null)
    {
        $page = (int) (input('page', $page) ?: 1);
        $size = (int) (input('size', $size) ?: 999);
        $search = input('search', $search);
        if (is_string($search) && $search !== '') {
            $decoded = json_decode($search, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $search = $decoded;
            }
        }
        if (!is_array($search)) {
            $search = is_string($search) && $search !== '' ? ['name' => $search] : [];
        }
        // 兼容前端 GET 扁平参数：name / is_visible
        if (input('?name') && input('name') !== '') {
            $search['name'] = input('name');
        }
        if (input('?is_visible') && input('is_visible') !== '') {
            $search['is_visible'] = input('is_visible');
        }
        $article=(new QyFactory())->instance('CmsService');
        $data=$article->get_category_list($page,$size,$search ?: null);
        $items=$data->getCollection();
        $data_list = subTree($items);
        $res['total'] = $data->total();
        $res['data'] = $data_list;
        return app('json')->success($res);
    }

    /**
     * 更新分类排序
     * @return int
     */
    public function setSort()
    {
        $arr=input('post.');
        return CategoryModel::setSort($arr);
    }



}