<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 10:44
 */

namespace app\model;


use bases\BaseModel;


class Category extends BaseModel
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
     * @return int
     */
    public static function addCategory($form)
    {
        if ($form['pid'] == 0) {
            $form['level'] = 1;
        } else {
            $form['level'] = 2;
        }
        $form['sort'] = 0;
        // 默认显示；若前端显式传入 0/1 则尊重
        if (!isset($form['is_visible']) || $form['is_visible'] === '' || $form['is_visible'] === null) {
            $form['is_visible'] = 1;
        } else {
            $form['is_visible'] = (int) $form['is_visible'] ? 1 : 0;
        }
        // 未上传图片时不能写 null（表字段 NOT NULL）
        if (!isset($form['category_pic']) || $form['category_pic'] === null || $form['category_pic'] === '') {
            $form['category_pic'] = '';
        }
        unset($form['imgs'], $form['id']);

        $res = self::create($form);
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
    }

    /**
     * 修改分组
     * @param $form
     * @return int
     */
    public static function editCategory($form)
    {
        //$form = json_decode($form, true);
        $form_data['id'] = $form['id'];
        $form_data['name'] = $form['name'];
        $form_data['short_name'] = $form['short_name'];
        $form_data['pid'] = $form['pid'];
        $form_data['category_pic'] = $form['category_pic'] ?? '';
        if ($form_data['category_pic'] === null) {
            $form_data['category_pic'] = '';
        }
        $form_data['sort'] = $form['sort'];
        if ($form['pid'] == 0) {
            $form_data['level'] = 1;
        } else {
            $form_data['level'] = 2;
        }
        $res = self::where(['id' => $form['id']])->update($form_data);
        return app('json')->success();
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
        $result = self::where(['id' => $id])->delete();
        if (!$result) {
            return app('json')->fail();
        }
        return app('json')->success();
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
        if (!$data || count($data) < 1) {
            return;//throw new BaseException(['msg'=>'获取商品分类失败或无数据']);app('json')->fail('获取商品分类失败或无数据');
        }
        return app('json')->success($data);
//        return $data;
    }

    /**
     * 获取所有分类信息
     * @param bool $vs
     * @return \think\Collection
     */
    public static function getCategoryAll($vs=false)
    {
        if($vs){
            $data=self::with('imgs')->order('sort asc')->select();
        }else{
            $data=self::with('imgs')->where('is_visible',1)->order('sort asc')->select();
        }

        if(!$data || count($data)<1){
            app('json')->fail('请至少添加一个商品分类');
        }
        return app('json')->success('操作成功',$data);
//        return $data;
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
//        return  json($data);
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
            self::update(['sort'=>$v],['id'=>$k]);
        }
        return app('json')->success();
    }

}