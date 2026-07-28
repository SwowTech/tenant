<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 10:06
 */

namespace app\controller\cms;


use app\controller\common\Search;
use app\model\Goods as GoodsModel;
use app\model\Code as CodeModel;
use app\services\CopyProductService;
use app\validate\IDPostiveInt;
use app\validate\ProductValidate;
use bases\BaseController;
use exceptions\TipException;
use think\Exception;
use think\facade\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use think\facade\Log;
use think\facade\Session;
use think\response\Json;
use think\facade\Db;
use app\model\Batch as BatchModel;
use Throwable;


/**
 *
 */
class ProductManage extends BaseController
{

    /**
     * cms 添加商品
     */
    public function addProduct()
    {

        $validate = new ProductValidate();
        $validate->goCheck();
        $post = input('post.');
        return GoodsModel::addProduct($post);
    }

    /**
     * 导入excel溯源码
     * @throws Exception
     */
    public function importCode()
    {
        $session = getSessionProcessMsg('import_code_progress');
        $session['command'] = 'doing';
        $session['count'] = 0;
        $session['insert_count'] = 0;
        $session['total'] = 0;
        setSessionProcessMsg('import_code_progress', $session);

        $root_path = get_public_path();
        $eid = intval(request()->param('eid'));
        $batch_id = intval(request()->param('batch_id'));
        if(!$batch_id){
            $session_value=['act'=>'error','msg' =>'出厂批次id不合法'];
            setSessionProcessMsg('import_code_progress',$session_value);
            throw new TipException('出厂批次id不合法');
        }
        if (!empty($_FILES['file']['name'])) {
            $fileName = $_FILES['file']['name'];    //得到文件全名
            $dotArray = explode('.', $fileName);    //把文件名安.区分，拆分成数组
            $type = end($dotArray);
            if ($type != "xls" && $type != "xlsx") {
                $ret['res'] = "0";
                $ret['msg'] = "不是Excel文件，请重新上传!";
                $session_value=['act'=>'error','msg' =>$ret['msg']];
                setSessionProcessMsg('import_code_progress',$session_value);
                return  app('json')->fail($ret['msg']);
            }

            //取数组最后一个元素，得到文件类型
            try {
                $uploaddir = tenant_upload_dir('xls') . DIRECTORY_SEPARATOR;
            } catch (\Throwable $e) {
                $session_value=['act'=>'error','msg' =>'创建目录失败'];
                setSessionProcessMsg('import_code_progress',$session_value);
                return app('json')->fail('创建目录失败');
            }

            $path = $uploaddir . time() . $type; //产生随机文件名
            //$path = "images/".$fileName; //客户端上传的文件名；
            //下面必须是tmp_name 因为是从临时文件夹中移动
            $res = move_uploaded_file($_FILES['file']['tmp_name'], $path); //从服务器临时文件拷贝到相应的文件夹下
            if(!$res){
                $session_value=['act'=>'error','msg' =>'保存文件失败'];
                setSessionProcessMsg('import_code_progress',$session_value);
                return app('json')->fail('保存文件失败');
            }
            $file_path = $path;
            if (!file_exists($path)) {
                $ret['res'] = "0";
                $ret['msg'] = "上传文件丢失!" . $_FILES['file']['error'];

                $session_value=['act'=>'error','msg' =>'上传文件丢失'];
                setSessionProcessMsg('import_code_progress',$session_value);

                return app('json')->fail($ret['msg']);
            }
            $data =[];
            $inserted_num = $this->importExecl($file_path,0,1,$data,$batch_id);

            return app('json')->success('插入成功');

        } else {
            $ret['res'] = "0";
            $ret['msg'] = "上传文件失败!";

            $session_value=['act'=>'error','msg' =>'上传文件失败'];
            setSessionProcessMsg('import_code_progress',$session_value);

            return app('json')->success($ret['msg']);
        }

    }

    /**
     * 使用PHPEXECL导入
     *
     * @param string $file      文件地址
     * @param int    $sheet     工作表sheet(传0则获取第一个sheet)
     * @param int    $columnCnt 列数(传0则自动获取最大列)
     * @param array  $options   操作选项
     *                          array mergeCells 合并单元格数组
     *                          array formula    公式数组
     *                          array format     单元格格式数组
     *
     * @return integer
     * @throws Exception
     */
    function importExecl(string $file = '', int $sheet = 0, int $columnCnt = 1, &$options = [], $batch_id=null)
    {
        try {
            $time = time() ;
            $insert_count = 0;//实际插入行数

            /* 转码 */
            $file = iconv("utf-8", "gb2312", $file);

            if (empty($file) OR !file_exists($file)) {
                throw new \Exception('文件不存在!');
            }

            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');

            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');

                if (!$objRead->canRead($file)) {
                    throw new \Exception('只支持导入Excel文件！');
                }
            }

            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            /* 获取指定的sheet表 */
            $currSheet = $obj->getSheet($sheet);

            if (isset($options['mergeCells'])) {
                /* 读取合并行列 */
                $options['mergeCells'] = $currSheet->getMergeCells();
            }

            if (0 == $columnCnt) {
                /* 取得最大的列号 */
                $columnH = $currSheet->getHighestColumn();
                /* 兼容原逻辑，循环时使用的是小于等于 */
                $columnCnt = Coordinate::columnIndexFromString($columnH);
            }

            /* 获取总行数 */
            $rowCnt = $currSheet->getHighestRow();
            $data   = [];
            $total = $rowCnt;
            /* 读取内容 */
            for ($_row = 1; $_row <= $rowCnt; $_row++) {
                $isNull = true;

                $command = getSessionProcessMsg('import_code_progress');
               if (isset($command['command']) && $command['command'] == 'stop') {
                    die();
                }

                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId   = $cellName . $_row;
                    $cell     = $currSheet->getCell($cellId);

                    $command = getSessionProcessMsg('import_code_progress');
                   if (isset($command['command']) && $command['command'] == 'stop') {
                        die();
                    }

                    if (isset($options['format'])) {
                        /* 获取格式 */
                        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                        /* 记录格式 */
                        $options['format'][$_row][$cellName] = $format;
                    }

                    if (isset($options['formula'])) {
                        /* 获取公式，公式均为=号开头数据 */
                        $formula = $currSheet->getCell($cellId)->getValue();

                        if (0 === strpos($formula, '=')) {
                            $options['formula'][$cellName . $_row] = $formula;
                        }
                    }

                    if (isset($format) && 'm/d/yyyy' == $format) {
                        /* 日期格式翻转处理 */
                        $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                    }

                    $data[$_row]['code'] = trim($currSheet->getCell($cellId)->getFormattedValue());
                    if(!$data[$_row]['code']){
                        $total--;
                    }

                    $data[$_row]['batch_id'] = $batch_id;

                    $data[$_row]['create_time'] = $time;

//                    if ( ($_row) % 500 == 0 || $_row == $rowCnt) {

                        $command = getSessionProcessMsg('import_code_progress');
                        if (isset($command['command'])&&$command['command'] == 'stop') {
                            die();
                        }
                        try{
                            $insert_count += CodeModel::insert($data[$_row]);
                        }catch(throwable $e){
                            Log::error($e->getMessage());
                        }

                        $session_value = ['count'=>$_row,'insert_count'=>$insert_count,'total'=>$total,'act' =>'importing'];
                        setSessionProcessMsg('import_code_progress',$session_value);
//                    }

                    if (!empty($data[$_row]['code'])) {
                        $isNull = false;
                    }
                }

                /* 判断是否整行数据为空，是的话删除该行数据 */
                if ($isNull) {
                    unset($data[$_row]);
                }
            }
            $session_value = [
                'count'=>$total-1,
                'insert_count'=>$insert_count,
                'msg' =>'导入成功',
                'total'=>$total,
                'act'=>'over'
            ];
            setSessionProcessMsg('import_code_progress',$session_value);
            return $insert_count;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * cms 修改商品
     * @return int
     */
    public function editProduct()
    {
        $validate = new ProductValidate;
        $validate->rule('goods_id', 'require|number');//新增一个验证规则，不让其过滤goods_id
        $validate->goCheck();
        $post = input('post.');    //$validate->getDataByRule(input('post.'));//param数据对sku的操作会有问题
        return GoodsModel::editProduct($post);
    }

    /**
     * cms 删除商品
     * @param string $id
     * @return int
     */
    public function deleteProduct($id = '')
    {
        $ids = is_array($id) ? $id : [$id];
        if (!is_array($id)) {
            (new IDPostiveInt())->goCheck();
        }
        if (!$ids) {
            return app('json')->fail('请选择商品');
        }
        foreach ($ids as $goodsId) {
            if (!GoodsModel::where('goods_id', $goodsId)->find()) {
                return app('json')->fail('商品不存在');
            }
            if (!GoodsModel::canDelete($goodsId)) {
                throw new TipException('请先删除该商品下的批次');
            }
        }
        foreach ($ids as $goodsId) {
            GoodsModel::deleteGoods($goodsId);
        }
        return app('json')->success();
    }

    /**
     * cms 删除商品
     * @param string $id
     * @return int
     */
    public function deleteCode($id = '')
    {
        CodeModel::assertCanDeleteByIds($id);

        if (is_array($id)) {
            CodeModel::destroy($id);
        } else {
            (new IDPostiveInt())->goCheck();
            $good_one = CodeModel::where(['id' => $id])->find();
            CodeModel::destroy($id);
            if (!$good_one) {
                return app('json')->fail();
            }
            $result = $good_one->delete(config('setting.soft_del'));   //这里是软删除
            if (!$result) {
                return app('json')->fail();
            }
        }

        return app('json')->success();
    }

    /**
     * cms 删除商品
     * @param string $id
     * @return int
     */
    public function deleteAll($goods_id='')
    {

        if(is_numeric($goods_id)){
            CodeModel::assertCanDeleteByQuery(CodeModel::where('goods_id', $goods_id));

            //$result = CodeModel::where('goods_id' , $goods_id)->delete();
            GoodsModel::where('goods_id', $goods_id)->update(['code_count' => 0]);
            $result = Db::table('xlsy_code')->where('goods_id', $goods_id)->delete();
            if (!$result) {
                return app('json')->fail();
            }
        }

        return app('json')->success();
    }

    /**
     * cms 查询code带保存功能
     */
    public function search($key = '',$eid=null,$is_save=false)
    {

        set_time_limit(0);
        $key = json_decode($key);

        $where = [];

        $obj = CodeModel::where('goods_id',$eid);

        if(($key->date)){
            $obj = $obj->whereBetweenTime('create_time',$key->date[0]/1000, $key->date[1]/1000);
        }
        if(($key->code)){
            $obj = CodeModel::where('goods_id',$eid)->where('code',$key->code);
        }


        $total = $obj->select()->count();
        $data = $obj->limit(10)->select()->toArray();

        $ret=[
            'data'=> $data,
            'total' => $total,
            'errcode' =>0
        ];
        return app('json')->success($ret);
    }

    /**
     * 更新分类排序
     * @return int
     */
    public function setSort()
    {
        $arr = input('post.');
        return GoodsModel::setSort($arr);
    }

    /**
     * 获取所有上架商品，包含分页
     * @param string $key
     * @return \think\response\Json
     */
    public function getProductList($page=1,$size=10,$order='',$state='',$search = '')
    {
        return GoodsModel::getProductByPage($page,$size,$order='',$state,$search);
    }

    /**
     * 获取上架商品，包含分页
     * @param string $key
     * @return \think\response\Json
     */
    public function getProduct($id)
    {
        return GoodsModel::getProductByAdmin($id);
    }
    /**
     * 获取所有下架商品
     * @return \think\response\Json
     */
    public function getProductsDown($page=1,$size=10)
    {
        $data = GoodsModel::with('imgs')
            ->where('state',1)
            ->order('create_time desc')
            ->limit(($page-1)*$size,$size)
            ->select();
        return app('json')->success($data);
    }
    /**
     * 获取所有在线商品
     * @return \think\response\Json
     */
    public function getOnlineProducts($page=1,$size=10)
    {
        $data = GoodsModel::with('imgs')
            ->where('state',0)
            ->order('create_time desc')
            ->limit(($page-1)*$size,$size)
            ->select();
        return app('json')->success($data);
    }
    /**
     * 获取所有热卖商品
     * @return \think\response\Json
     */
    public function getHotProducts($page=1,$size=10)
    {
        $data = GoodsModel::with('imgs')
            ->where('is_hot',1)
            ->limit(($page-1)*$size,$size)
            ->order('create_time desc')
            ->select();
        return app('json')->success($data);
    }
    /**
     * 获取所有最新商品
     * @return \think\response\Json
     */
    public function getNewProducts($page=1,$size=10)
    {
        $data = GoodsModel::with('imgs')
            ->where('is_new',1)
            ->limit(($page-1)*$size,$size)
            ->order('create_time desc')
            ->select();
        return app('json')->success($data);
    }
    /**
     * $id 商品ID
     * mcms获取所有商品简略
     * @return \think\Collection
     */
    public function allGoodsInfo($page=0,$size=10,$order='',$search = [],$id='',$status='all')
    {
        $obj = GoodsModel::with('imgs')
            ->field('batch_count,code_count,scan_count,goods_id,sort,code_count,goods_name,is_hot,is_new,market_price,price,stock,sales,state,img_id');

        if($status=='new'){
            $obj->where('is_new',1);
        }
        if($status=='hot'){
            $obj->where('is_hot',1);
        }
        if($status=='online'){
            $obj->where('state',1);
        }
        if($status=='offline'){
            $obj->where('state',0);
        }
        if (!empty($order)) {
            $order_arr=[];
            foreach($order as $k => $v){
                if($v[1]=='descending' || $v[1]=='ascending'){
                    if($v[1]=='descending' ){
                        $type = 'desc';
                    }
                    if($v[1]=='ascending'){
                        $type = 'asc';
                    }
                    $order_arr[$v[0]] = $type;
                }
            }
            $obj->order($order_arr);
        }else{
            $obj->order(['sort'=>'desc','goods_id'=>'desc']);
        }
        foreach($search as $k => $v){
            if($v){
                if($k=='goods_id'){
                    $obj->where($k,$v);
                }else{
                    $obj->where($k, 'like', '%' . $v . '%');
                }
            }
        }
        if((int)$id){
            $obj->where('goods_id',$id);
        }
        $total = $obj->count();
        $res = $obj->limit(($page-1)*$size,$size)->select();
        $data=[
            'data'=>$res,
            'total'=>$total
        ];
        return app('json')->success($data);
    }

    /**
     * mcms获取某个商品的溯源码
     * @return \think\Collection
     */
    public function getAllCodes($eid='',$page=1,$size=10,$batch_id='',$search='')
    {

        $obj = CodeModel::order('id','desc');
        $page = (int) (input('page', $page) ?: 1);
        $size = (int) (input('size', $size) ?: 10);
        $search = input('search', $search);
        if (!is_array($search)) {
            $search = [];
        }
        $batch_id = $batch_id ?: ($search['batch_id'] ?? '');
        if($batch_id){
            $obj->where('batch_id',$batch_id);
        }
        if($eid){
            $batch_ids = BatchModel::where('goods_id', $eid)->column('id');
            if ($batch_ids) {
                $obj = $obj->whereIn('batch_id', $batch_ids);
            }
        }
        if(isset($search['date']) && $search['date']){
            $obj = $obj->whereBetweenTime('create_time',$search['date'][0]/1000, $search['date'][1]/1000);
        }
        if(isset($search['code']) && $search['code']){
            $obj = $obj->where('code','like','%'.$search['code'].'%');
        }
        $total =  $obj->count();

        $res = $obj->with(['batch.goods','writeoff_info'])
            ->limit(($page-1)*$size,$size)
            ->select();

        $data = [
            'data'=>$res,
            'total'=>$total
        ];
        return app('json')->success($data);
    }


    /**
     * mcms获取所有商品名称ID
     * @return \think\Collection
     */
    public function allGoodsName()
    {
        $res = GoodsModel::field('goods_id,goods_name')->select();
        return app('json')->success($res);
    }

    /**
     * 手机cms 修改商品
     * @return int
     */
    public function mobileEditProduct()
    {
        $rule = [
            'goods_id' => 'require',
            'goods_name' => 'require',//名称
            'stock' => 'require',  //库存
            'sales' => 'max:200',  //销量
            'market_price' => 'require|isNotEmpty', //市场价格
            'price' => 'require', //单价
        ];
        $post = input('post.');
        $this->validate($post, $rule);
        return GoodsModel::mobileEditProduct($post);
    }

    /**
     * 采集商品
     */
    public function getCopyProductInfo()
    {
        return (new CopyProductService())->getRequestContents();
    }

    /**
     * 获取为参加活动的商品
     * @return mixed
     */
    public function getNormalGoods()
    {
        $res = GoodsModel::getNormalGoods();
        return app('json')->success($res);
    }

}
