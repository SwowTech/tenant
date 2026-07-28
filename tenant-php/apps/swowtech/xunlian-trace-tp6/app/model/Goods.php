<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 13:00
 */

namespace app\model;


use bases\BaseModel;
use exceptions\BaseException;
use exceptions\OrderException;
use exceptions\ProductException;
use exceptions\TipException;
use think\Exception;
use think\facade\Db;
use app\model\Code as CodeModel;
use app\model\CodeResult as CodeResultModel;
use app\services\TokenService;
use Throwable;

class Goods extends BaseModel
{
    //use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $hidden = ['is_stock_visible', 'is_pre_sale', 'shipping_fee', 'is_bill'];

    //关联图片
    public function imgs()
    {
        return $this->belongsTo('Image', 'img_id', 'id');
    }

    public function favorite()
    {
        return $this->hasOne('Favorites', 'fav_id', 'goods_id');
    }

    /**
     * 添加商品
     * @param $post
     * @return int
     * @throws BaseException
     */
    public static function addProduct($post)
    {
        //多用户id
        $post['uid'] = TokenService::getCurrentAid();
        Db::startTrans();// 启动事务
        try {
            if(!$post['banner_imgs']){
                $post['banner_imgs'] = '';
            }

            $res = self::create($post);
            Db::commit();
            if ($res) {
                return app('json')->success();
            }
            return app('json')->fail();
        } catch (throwable $e) {
            Db::rollback();
            throw new TipException($e->getMessage());
            //throw new ProductException(['msg' => '商品添加失败']);
        }
    }

    /**
     * 修改商品
     * @param $post
     * @return int
     * @throws BaseException
     */
    public static function editProduct($post)
    {
        Db::startTrans();
        try {
            if (!$post['banner_imgs']) {
                $post['banner_imgs'] = '';
            }
//            $post['img_id'] = substr($post['banner_imgs'], 0, 1);
//            $post['diyContent'] = json_encode($post['diyContent']);
            $res = self::update($post, ['goods_id' => $post['goods_id']]);
            Db::commit();
            if (!$res) {
                return app('json')->fail();
            }
            return app('json')->success();
        } catch (\throwable $e) {
            // 回滚事务
            Db::rollback();
            throw new ProductException(['msg' => '商品修改失败' . $e->getMessage()]);
        }
    }

    /**
     * 排序
     * @param $arr
     * @return int
     */
    public static function setSort($arr)
    {
        if (!is_array($arr)) {
            return app('json')->fail();
        }
        foreach ($arr as $k => $v) {
            self::update(['sort' => $v], ['goods_id' => $k]);
        }
        return app('json')->success();
    }


    /**
     * 获取商品详情
     * @param $id
     * @return \think\response\Json
     */
    public static function getProduct($id)
    {
        $uid = TokenService::getCurrentUid();

        $res = self::with(['imgs', 'favorite' => function ($query) use ($uid) {
            $query->where('uid', $uid);
        }])->where('goods_id', $id)->find();

        $url = [];
        $list = [];
        if (!empty($res['banner_imgs'])) {
            $imgs = Image::where('id', 'in', $res['banner_imgs'])->select();
            foreach ($imgs as $k => $v) {
                $url[$k] = $v['url'];
                $list[$k] = $v;
            }
            $res['banner_imgs'] = explode(',', $res['banner_imgs']);
            $res['banner_imgs_url'] = $url;
            $res['banner_imgs_list'] = $list;
        }else{
            $res['banner_imgs'] = [];
            $res['banner_imgs_url'] = [];
            $res['banner_imgs_list'] = [];
        }

        return app('json')->success($res);
    }
    /**
     * 获取某商品详情
     * @param $id
     * @return \think\response\Json
     */
    public static function getProductByAdmin($id)
    {
        $res = self::with(['imgs'])->append(['BannerImageList'])->where('goods_id', $id)->find();
        return app('json')->success($res);
    }
    /**
     * 获取所有、最新、最热、推荐商品
     * @param $type
     * @return \think\Collection|void
     */
    public static function getRecentAll($type,$limit= 1000)
    {
        $where['state'] = 1;
        if ($type == 'new') {
            $data = self::with('imgs')->where('is_new', 1)->where($where)->order('sort desc')->limit($limit)->select();
        } else if ($type == 'hot') {
            $data = self::with('imgs')->where('is_hot', 1)->where($where)->order('sort desc')->limit($limit)->select();
        } else if ($type == 'recommend') {
            $data = self::with('imgs')->where('is_recommend', 1)->where($where)->order('sort desc')->limit($limit)->select();
        } else {
            $data = self::with('imgs')->where($where)->order('sort desc')->limit(6)->select();
        }
        if (!$data || count($data) < 1) {
            return;//throw new BaseException(['msg'=>'获取最新商品失败或无数据']);
        }
        return $data;
    }

    /**
     * 获取所有、最新、最热、推荐商品
     * @param $type
     * @return \think\Collection|void
     */
    public static function getRecent($type)
    {
        $where['state'] = 1;
        if ($type == 'new') {
            $data = self::with('imgs')->where('is_new', 1)->where($where)->order('sort desc')->limit(10)->select();
        } else if ($type == 'hot') {
            $data = self::with('imgs')->where('is_hot', 1)->where($where)->order('sort desc')->limit(10)->select();
        } else if ($type == 'recommend') {
            $data = self::with('imgs')->where('is_recommend', 1)->where($where)->order('sort desc')->limit(10)->select();
        } else {
            $data = self::with('imgs')->where($where)->order('sort desc')->limit(10)->select();
        }
        if (!$data || count($data) < 1) {
            return;//throw new BaseException(['msg'=>'获取最新商品失败或无数据']);
        }

        return $data;
    }



    /**
     * 获取所有上架商品，包含分页
     * @param int $order
     * @param string $search
     * @param int $state
     * @param int $page
     * @param int $size
     * @return \think\response\Json
     */
    public static function getProductByPage($page=0,$size=10,$order='',$state='',$search = '')
    {

        $obj = self::with('imgs')->limit(($page-1)*$size,$size);
        if (!empty($search)) {
            foreach($search as $k => $v){
                if($v){
                    if($k=='goods_id'){
                        $obj->where($k,$v);
                    }else{
                        $obj->where($k, 'like', '%' . $v . '%');
                    }
                }
            }
        }
        if (!empty($state)) {
            foreach($state as $k => $v){
                if($v){
                    $obj->where($k,  $v );
                }
            }
        }
        if (!empty($order)) {
            $obj->order($order);
        }
        $res = $obj->select();
        $total = $obj->count();

        $data=[
          'data'=>$res,
          'total'=>$total
        ];
        return app('json')->success($data);
    }

    /**
     * 获取所有下架商品，包含分页
     * @param int $page
     * @param int $size
     * @return \think\response\Json
     */
    public static function getProductDownByPage()
    {
        $data = self::with('imgs')->where('state', 0)->order('create_time desc')->select();
        return app('json')->success($data);
    }

    /**
     * ID获取某商品及关联详情
     * @param $id
     * @return array|null|\think\Model
     * @throws BaseException
     */
    public static function getProductByID($id)
    {
        $data = self::with(['sku'])->where('goods_id', $id)->find();
        if (!$data) {
            throw new BaseException(['商品不存在或数据错误']);
        }
        return $data;
    }


    /**
     * name获取某商品详情
     * @param $name
     * @return \think\Collection
     */
    public static function getProductByName($name)
    {
        $data = self::with('imgs')->where('state', 1)->where('goods_name', 'like', '%' . $name . '%')
            ->order('sales desc')->select();
        return $data;
    }

    /**
     * 检查库存少于10的商品
     * @return int
     */
    public static function getGoodsStock()
    {
        $goods = self::with('goodsSku')->where('state', 1)->select();
        $goods_stock = 0;
        foreach ($goods as $k => $v) {
            if ($v['goods_sku']) {
                foreach ($v['goods_sku']['json']['list'] as $key => $value) {
                    if ($value['stock_num'] < 10) {
                        $goods_stock += 1;
                        break;
                    }
                }
            } else {
                if ($v['stock'] < 10) {
                    $goods_stock += 1;
                }
            }
        }
        return $goods_stock;
    }
    /**
     * 获取商品总数
     * @return int
     */
    public static function getGoodsCount()
    {
        $goods_count = self::count();

        return $goods_count;
    }
    /**
     * 检测库存
     * @param $data
     * @return int
     * @throws OrderException
     */
    public static function checkStock($data)
    {
        foreach ($data as $k => $v) {
            $goods = self::with('goodsSku')->where('goods_id', $v['goods_id'])->find();
            if ($v['sku_id']) {
                foreach ($goods['goods_sku']['json']['list'] as $key => $value) {
                    if ($v['sku_id'] == $value['id']) {
                        if ($value['stock_num'] < $v['num']) {
                            throw new OrderException(['msg' => '库存不足']);
                        }
                    }
                }
            } else if ($v['num'] > $goods['stock']) {
                throw new OrderException(['msg' => '库存不足']);
            }
        }
        return 1;
    }

    /**
     * 修改库存
     * @param $data
     * @return int
     * @throws OrderException
     */
    public static function editStock($data)
    {
        foreach ($data as $k => $v) {
            $goods = self::with('goodsSku')->where('goods_id', $v['goods_id'])->find();
            if ($v['sku_id']) {
                foreach ($goods['goods_sku']['json']['list'] as $key => $value) {
                    if ($v['sku_id'] == $value['id']) {
                        $goods['goods_sku']['json']['list'][$key]['stock_num'] = $value['stock_num'] - $v['num'];
                        if ($goods['goods_sku']['json']['list'][$key]['stock_num'] >= 0) {
                            $goods->save();
                        }
                    }
                }
            } else {
                $goods['stock'] = $goods['stock'] - $v['num'];
                if ($goods['stock'] >= 0) {

                    $goods->save();
                }
            }
        }
        return 1;
    }


    /**
     * 商品下是否还有批次（有则不允许直接删除）
     */
    public static function canDelete($id)
    {
        return Batch::where('goods_id', $id)->count() <= 0;
    }

    /**
     * cms 删除商品（需先清空批次）
     * @param $id
     */
    public static function deleteGoods($id = '')
    {
        if (!self::canDelete($id)) {
            throw new TipException('请先删除该商品下的批次');
        }
        CodeModel::deleteByGoodsId($id);
        CodeResultModel::deleteByGoodsId($id);
        Batch::where('goods_id', $id)->delete();
        self::where('goods_id', $id)->delete();
    }
    public function getBannerImgsList()
    {
        $list = [];
        if (!empty($this->banner_imgs)) {
            $list = Image::where('id', 'in', $this->banner_imgs)->select();
        }
        foreach ($list as $key=>$value){
            if(!$value){
                array_splice($list,$key,$key+1);
            }
        }
        $this->banner_imgs_list = $list;
        return $this ;
    }

    public function getBannerImageListAttr($value)
    {
        $list = [];
        $imgs = $this->getData('banner_imgs');
        if (!empty($imgs)) {
            $imgs = explode(',', $imgs);
            foreach ($imgs as $key => $value) {
                $list[] = Image::where('id', $value)->field('id,url')->find();
            }
        }
        return $list;
    }

    public function getMarketPriceAttr($value)
    {
        return floatval($value);
    }

    public function getDiyContentAttr($value)
    {
        return $value ? json_decode($value, true)??[]:[];
    }

    public function setDiyContentAttr($value)
    {
        return $value ? json_encode($value, true) : [];
    }

}
