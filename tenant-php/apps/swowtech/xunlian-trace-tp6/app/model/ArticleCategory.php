<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 10:44
 */

namespace app\model;


use bases\BaseModel;
use exceptions\TipException;

class ArticleCategory extends BaseModel
{
    protected $updateTime = false;

    /**
     * 关联图片
     * @return \think\model\relation\BelongsTo
     */
    public function imgs()
    {
        return $this->belongsTo('Image', 'category_pic', 'id');
    }

    /**
     * 添加分类
     * @param $form
     * @return ArticleCategory|int|\think\Model
     */
    public static function addCategory($form)
    {
        if ($form['pid'] == 0) {
            $form['level'] = 1;
        } else {
            $form['level'] = 2;
        }
        $res = self::create($form);
        return $res;
    }

    /**
     * 修改分组
     * @param $form
     * @return int
     */
    public static function editCategory($form)
    {
        $form_data['id'] = $form['id'];
        $form_data['name'] = $form['name'];
        $form_data['short_name'] = $form['short_name'];
        $form_data['pid'] = $form['pid'];
        $form_data['category_pic'] = $form['category_pic'];
        if ($form['pid'] == 0) {
            $form_data['level'] = 1;
        } else {
            $form_data['level'] = 2;
        }
        self::where(['id' => $form['id']])->update($form_data);
        return true;
    }

    /**
     * 删除分组
     * @param $id
     * @return int
     */
    public static function deleteCategory($id)
    {
        $category_goods = Goods::where('category_id', $id)->count();
        if ($category_goods > 0) {
            return app('json')->fail('无法删除，该分类下有商品');
        }
        $res = self::where(['id' => $id])->delete();
        return $res;
    }

    /**
     * 获取X级分类
     * @param $id
     * @return \think\Collection|void
     */
    public static function getCategoryLevel($id)
    {
        $where['level'] = $id;
        $where['is_visible'] = 1;
        $data = self::with('imgs')->where($where)->order('sort asc')->select();
        if (!$data) {
            throw new TipException(['msg' => '获取商品分类失败或无数据']);
        }
        return app('json')->success($data);
    }

    /**
     * 获取所有分类信息
     * @param bool $vs
     * @return \think\Collection
     */
    public static function getAll($page =1,$size = 10,$search = null)
    {
        $data=self::with('imgs')
            ->order('sort asc')
            ->when(isset($search['name']) && $search['name'],function($query)use($search){
                $query->where('name','like','%'.$search['name'].'%');
            })
            ->limit(($page-1)*$size,$size)->select();
        return $data;
    }

    /**
     * 获取分类下所有子类与广告图
     * @param $id
     * @return \think\response\Json
     */
    public static function getCateChildImg($id)
    {
        $data['category'] = self::with('imgs')->where('pid',$id)->select();
        $banner = Banner::with(['items','items.imgs'])->find($id+1);
        $data['banner'] = $banner['items'];
        return app('json')->success($data);
    }


    /**
     * 排序
     * @param $arr
     * @return int
     */
    public static function setSort($arr){
        if(!is_array($arr)){
            return app('json')->fail();
        }
        foreach ($arr as $k=>$v){
            self::update(['sort'=>$v],['category_id'=>$k]);
        }
        return app('json')->success();
    }

}