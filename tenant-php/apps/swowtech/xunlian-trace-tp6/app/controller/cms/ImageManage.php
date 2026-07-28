<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/12 0012
 * Time: 13:11
 */

namespace app\controller\cms;


use app\model\Image;
use app\model\ImageCategory;
use app\model\ImageCategory as ImageCategoryModel;
use app\services\CommonService;
use app\validate\IDPostiveInt;
use bases\BaseController;

class ImageManage extends BaseController
{
    /**
     * 添加分类
     * @return mixed
     */
    public function addImageCategory()
    {
        $rule=['category_name'=>'require'];
        $post=input('post.');
        $this->validate($post,$rule);
        return ImageCategoryModel::addCategory($post);
    }

    /**
     * 删除分类
     * @param $id
     * @return mixed
     */
    public function deleteImageCategory($id='')
    {
        (new IDPostiveInt)->goCheck();
        return ImageCategoryModel::deleteCategory($id);
    }

    /**
     * 获取分类
     * @return mixed
     */
    public function getImageCategory()
    {
        $res=ImageCategoryModel::select();
        return app('json')->success($res);
    }

    /**
     * 获取所有图片
     * @return mixed
     */
    public function getAllImage( $page=1,$size=50,$cate_id=0){

        $obj = Image::where('is_visible',1)->order('id','desc');
        if($cate_id){
            $obj->where('category_id',$cate_id);
        }
        $res['total'] = $obj->count();
        $res['imgs'] = $obj->limit(($page-1)*$size,$size)->select();
        return app('json')->success($res);
    }

    /**
     * 删除图片
     * @param $ids
     * @return mixed
     */
    public function editImage($ids){
        $res=Image::where(['id'=>$ids])->update(['is_visible'=>0]);
        if($res){
            return app('json')->success();
        }else{
            return app('json')->fail();
        }
    }

    /**
     * 修改图片分类
     * @param $ids
     * @return mixed
     */
    public function editImageCategory($id,$category_name){
        $res=ImageCategory::update(['category_name'=>$category_name],['id'=>$id]);
        return app('json')->success();
    }

    /**
     * 合并分类
     * @return mixed
     */
    public function combineCategory($id,$combine_id)
    {
        if($id && $combine_id){
            $res = Image::where('category_id',$id)->update(['category_id'=>$combine_id]);
        }else{
            return app('json')->fail();
        }
        return app('json')->success($res);
    }

}
