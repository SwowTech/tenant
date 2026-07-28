<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 10:35
 */

namespace app\controller\common;


use app\controller\common\MiniWechat;
use app\model\DiscountGoods as DiscountGoodsMdoel;
use app\model\Goods as GoodsModel;
use app\model\Rate as RateModel;
use app\model\Region;
use app\model\SysConfig as SysConfigModel;
use app\Request;
use app\services\AdminService;
use app\services\TokenService;
use app\validate\IDPostiveInt;
use bases\BaseController;
use app\model\Category as CategoryModel;
use EasyWeChat\Work\Auth\AccessToken;
use think\facade\Log;
use app\model\Code as CodeModel;
use app\model\CodeResult as CodeResultModel;
use think\Response;
use think\facade\Cache;
use Exception;
use Throwable;
use app\model\CodeWriteoff as CodeWriteoffModel;
use exceptions\TokenException;
use app\model\Batch as BatchModel;
use think\facade\Session;
use app\model\User as UserModel;

class Product extends BaseController
{
    /**
     * 获取某商品详情
     * @param string $id
     * @return \think\response\Json
     */
    public function getProduct($id = '')
    {
        (new IDPostiveInt())->goCheck();
        return GoodsModel::getProduct($id);
    }

    /**
     * 获取最新商品
     * @param string $type
     * @return \think\Collection|void
     */
    public function getRecent($type = '')
    {
        $data = GoodsModel::getRecent($type);
        return app('json')->success($data);
    }

    /**
     * 获取优惠券能使用商品
     * @param $id
     * @return \think\Collection|void
     */
    public function getCouponProduct($id)
    {
        (new IDPostiveInt())->goCheck();
        $data = GoodsModel::getCouponProduct($id);
        return app('json')->success($data);
    }

    /**
     * 获取某分类下所有商品
     * @param string $id
     * @return \think\response\Json
     */
    public function getCatePros($id='')
    {
        (new IDPostiveInt())->goCheck();
        $ids=CategoryModel::where('pid',$id)->column('category_id');
        if($ids){
            array_push($ids,$id);
        }else{
            $ids=$id;
        }
        $data = GoodsModel::with('imgs')->where(['state'=>1,'category_id'=>$ids])->select();
        return app('json')->success($data);
    }
    /**
     * 获得某商品的二维码
     * @param $request
     * @return \think\response\Json
     */
    public function downloadMiniQrcode(Request $request)
    {

        //小程序
        $app = MiniWechat::getMiniWechat();

        //删除qrcode目录


        $root_path = get_public_path();

        $userId = TokenService::getCurrentAid();
        $workDir = $root_path.'/qrcode/'. $userId;
        if (file_exists($workDir)) {
            delDirAndFile($workDir);
        }

        $product_ids = $request->only(['product_ids']);
        $product_ids = $product_ids['product_ids'];
        if(!$product_ids){
            return app('json')->fail('请选择商品');
        };
        $total = count($product_ids);
        $count = 0;

        set_time_limit(0);
        if ((session_status() != PHP_SESSION_ACTIVE)) {
            session_start();
        }
        $_SESSION['qr_create_percent'] = ['count'=>0,'total'=>$total,'down_url'=>'','msg' =>'开始生成'];
        session_write_close();  //释放session锁

        foreach ($product_ids as $k => $product) {
            $count++;
            $id = $product['goods_id'];

            $categories = self::getParentCategories($id);
            $categories = array_reverse($categories);

            //拼接目录用分类id和名称
            $path = '';
            foreach ($categories as $k => $v) {
                $path_name =  $v['short_name'];
                $path_name = str_replace(['/','\\',':','*','"','<','>','|','?'],'_',$path_name);
                $path = $path . $v['id'] .'--'. $path_name.'/';

            }
            //生成按分类为目录，商品为文件名的目录
            // $dir = iconv("UTF-8", "UTF-8", $root_path."/qrcode/" . $path);

            $dir = $workDir .'/'. $path;
            $goods_name = $product['goods_id'].'--'.$product['goods_name'];
            //$goods_name = iconv("UTF-8", "UTF-8", $goods_name);
            //删除特殊字符
            $goods_name = str_replace(['/','\\',':','*','"','<','>','|','?'],'_',$goods_name);

            $file_full_path = $dir.$goods_name;

            if (!file_exists($dir)) {
                $res = mkdir($dir, 0777, true);
                if(!$res){
                    return app('json')->fail("创建目录($dir)出错");
                }
            }
            if (!file_exists($file_full_path)) {

                //保存小程序码
                $sceen = $product['goods_id'];
                try{
                    $response = $app->app_code->getUnlimit($sceen, [
                        'page'  => 'pages/index/index',
                        'width' => 600,
                    ]);
                    if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                        $filename = $response->saveAs($dir, $goods_name);
                    }else{
                        Log::error("保存小程序码出错");
                    }

                }catch(\Exception $e){
                    $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'msg' =>'保存小程序码出错','act'=>'minicode'];
                    session_write_close();
                }
                if ((session_status() != PHP_SESSION_ACTIVE)) {
                    session_start();
                }
                $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'msg' =>'正在生成小程序码','act'=>'minicode'];
                session_write_close();  //释放session锁
            } else {
                return app('json')->fail('商品二维码文件已存在');
            }
        }
        if ((session_status() != PHP_SESSION_ACTIVE)) {
            session_start();
        }
        $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'msg' =>'生成小程序码完成','act'=>'minicode'];
        session_write_close();
        if(count($product_ids)>0){

            $zip_name = $userId;
            if ((session_status() != PHP_SESSION_ACTIVE)) {
                session_start();
            }
            $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'msg' =>'正在压缩','act'=>'zip'];
            session_write_close();  //释放session锁

            try{
                $res = self::zip($workDir,$workDir.'/'.$zip_name.'.zip');
            }catch(Throwable | Exception $e){
                if ((session_status() != PHP_SESSION_ACTIVE)) {
                    session_start();
                }
                $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'msg' =>'压缩出错'.$e->getMessage(),'act'=>'zip'];
                session_write_close();  //释放session锁
                return app('json')->fail('失败','压缩出错'.$e->getMessage());
            }

            $url = get_public_url().'/qrcode/'.$userId.'/'.$userId.'.zip';
            $data = [
                'down_url'=>$url,
                'errcode'  =>0
            ];
            if ((session_status() != PHP_SESSION_ACTIVE)) {
                session_start();
            }
            $_SESSION['qr_create_percent'] = ['count'=>$count,'total'=>$total,'down_url'=>$url,'msg' =>'完成','act'=>'zip'];
            session_write_close();  //释放session锁

            if($res){
                return app('json')->success('成功',$data);
            }
        }
        return app('json')->fail('出错了');

    }
    /**
     * 生成小程序码进度查询
     * @param $request
     * @return \think\response\Json
     */
    public function qrCreateProgress()
    {
        $stop = request()->param('command');
        if ($stop) {
            $session = getSessionProcessMsg('qr_create_percent');
            $session['command'] = 'stop';
            $now_percent = setSessionProcessMsg('qr_create_percent', $session);

        } else {
            if ((session_status() != PHP_SESSION_ACTIVE)) {
                session_start();
            }
            $now_percent = @$_SESSION['qr_create_percent'];
            session_write_close();
        }

        return app('json')->success($now_percent);

    }

    /**
     * 获取某商家所有商品
     * @param $id
     * @return \think\Collection
     */
    public function getShopProduct($id)
    {
        (new IDPostiveInt)->goCheck();
        $data = GoodsModel::getShopID($id);
        return app('json')->success($data);
    }

    /**
     * 获取某分类下所有商品
     * @param string $id
     * @return \think\response\Json
     */
    public function getProductsByCategory($id = '',$page=1,$size=10)
    {
        (new IDPostiveInt())->goCheck();
        $ids = CategoryModel::where('pid', $id)->column('id');
        if ($ids) {
            array_push($ids, $id);
        } else {
            $ids = $id;
        }

        $data = GoodsModel::with('imgs')
            ->where(['state' => 1, 'category_id' => $ids])
            ->limit(($page-1)*$size,$size)
            ->select();
        $total = GoodsModel::where(['state' => 1, 'category_id' => $ids])->count();
        $res['data'] = $data;
        $res['total'] = $total;

        return app('json')->success($res);
    }

    /**
     * 获取某个商品的分类数组
     * @param $id
     * @return array 商品父级分类
     */
    public function getParentCategories($id)
    {
        $categories=[];
        $goods = GoodsModel::where('goods_id', $id)->find();
        if($goods){
            $pid = $goods->category_id;
            while ($pid !==  0) {
                $category = CategoryModel::where('id', $pid)->find();
                if($category){
                    $category = $category->toArray();
                    $categories[] = $category;
                    $pid = $category['pid'];
                }


            }
        }

        return $categories;
    }

    /**
     * 获取某个商品的所有评价
     * @param $id
     * @return \think\response\Json
     */
    public function getEvaluate($id)
    {
        (new IDPostiveInt)->goCheck();
        $pj = RateModel::with(['user' => function ($q) {
            $q->field('id,nickname,headpic');
        }])->where('goods_id', $id)->order('id desc')->select();
        return app('json')->success($pj);
    }
    /**
     * 获取某个商品的所有评价
     * @param $id
     * @return \think\response\Json
     */
    public function jumpH5Certify($g='',$c='')
    {
        if($c){
            header("Location:".get_public_url()."/h5/#/pages/certify_result/certify_result?&certify_code=$c");
        }else{
            header("Location:".get_public_url()."/h5/#/pages/products/productDetail/productDetail?id=$g");
        }
        exit();
    }

    /**
     * 查询溯源码
     * @param $request
     * @return \think\response\Json
     */
    public function certify($certify_code,$longitude='',$latitude='',$region=['','',''],$captcha_code='')
    {
        $data = CodeResultModel::getCertifyResultData($certify_code,$longitude, $latitude, $region, $captcha_code);
        return app('json')->success($data);
    }

    /**
     * 查询商品的小程序码
     * @param $request
     * @return \think\response\Json
     */
    public function getQrcode(Request $request)
    {
        //小程序
        $app = MiniWechat::getMiniWechat();
        //保存小程序码
        $id = $request['id'];
        $response = $app->app_code->getUnlimit($id, [
            'page'  => 'pages/index/index',
            'width' => 600,
        ]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
//            $filename = $response->saveAs($dir, $goods_name);
        }else{
            Log::error("保存小程序码出错");
        }

    }



    /**
     * @desc  生成zip压缩文件的函数
     *
     * @param $dir             string 需要压缩的文件夹名
     * @param $filename     string 压缩后的zip文件名  包括zip后缀
     * @param $missfile      array   不需要的文件
     * @param $fromString  array   自定义压缩文件
     */
    public function zip($dir, $filename, $missfile = array(), $addfromString = array())
    {

        if (!file_exists($dir) || !is_dir($dir)) {
            throw new Exception(' can not exists dir ' . $dir);
        }
        $ext_test = explode('.', $filename);
        if (strtolower(end($ext_test)) != 'zip') {
            throw new Exception('only Support zip files');
        }
        $dir = str_replace('\\', '/', $dir);
        $filename = str_replace('\\', '/', $filename);
        //$filename = iconv('utf-8', 'UTF-8', $filename);

        if (file_exists($filename)) {
            unlink($filename);
        }

        $files = array();
        self::getfiles($dir, $files);
//        $files    = self::arrayIconv($files);
        if (empty($files)) {
            throw new Exception(' qrcode目录为空');
        }
        $zip = new \ZipArchive();
        $res = $zip->open($filename, \ZipArchive::CREATE);
        if ($res === TRUE) {
            foreach ($files as $v) {
                if (!in_array(str_replace($dir . '/', '', $v), $missfile)) {
                    $zip->addFile($v, str_replace($dir . '/', './', $v));
                }
            }
            if (!empty($addfromString)) {
                foreach ($addfromString as $v) {
                    $zip->addFromString($v[0], $v[1]);
                }
            }
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    //定义图片字符集
    public function arrayIconv($data, $in_charset = 'GBK', $out_charset = 'UTF-8')
    {
        if (!is_array($data)) {
            $output = iconv($in_charset, $out_charset, $data);
        } elseif (count($data) === count($data, 1)) {//判断是否是二维数组
            foreach ($data as $key => $value) {
                $output[$key] = iconv($in_charset, $out_charset, $value);
            }
        } else {
            eval_r('$output = ' . iconv($in_charset, $out_charset, var_export($data, TRUE)) . ';');
        }
        return $output;
    }

    public function getfiles($dir, &$files = array())
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return mkdir($dir);
        }
        if (substr($dir, -1) == '/') {
            $dir = substr($dir, 0, strlen($dir) - 1);
        }
        $_files = scandir($dir);
        foreach ($_files as $v) {
            if ($v != '.' && $v != '..') {
                if (is_dir($dir . '/' . $v)) {
                    self::getfiles($dir . '/' . $v, $files);
                } else {
                    $files[] = $dir . '/' . $v;
                }
            }
        }
        return $files;
    }


}
