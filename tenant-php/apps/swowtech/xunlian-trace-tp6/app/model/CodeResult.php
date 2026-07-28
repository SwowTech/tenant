<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/16 0016
 * Time: 13:00
 */

namespace app\model;


use app\model\Admin as AdminModel;
use app\model\Goods as GoodsModel;
use app\model\User as UserModel;
use bases\BaseModel;
use exceptions\TipException;
use think\Exception;
use app\model\CodeResult as CodeResultModel;
use app\model\CodeWriteoff as CodeWriteoffModel;
use app\model\SysConfig as SysConfigModel;
use app\services\TokenService;
use think\facade\Log;
use app\model\Code as CodeModel;
use Throwable;
use utils\Captcha;

class CodeResult extends BaseModel
{
    //use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $type=[
//        'certify_time'=>  'timestamp:Y-m-d',
        ];


    public static $status=[
            0=>'正常',
            1=>'已验证',
            2=>'拉黑'
        ];
    protected function getCertifyTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);

    }
    /**
     * 查询溯源码
     * @param string $type 'h5或gzh(公众号)'
     * @return array
     */
    public static function getCertifyResultData($certify_code, $longitude = '', $latitude = '', $region = ['', '', ''], $captcha_code = '',$uid='',$type='h5')
    {
        if ($captcha_code) {

            if(!app(Captcha::class)->check($captcha_code)){
                //throw new TipException('验证码错误');
                $data['error_code'] = 100;
                $data['error_message'] = '验证码错误';
                return $data;
            }
//            $key = TokenService::getCurrentTokenVar('captcha');
//            mb_strtolower($captcha_code, 'UTF-8');
//            $res = password_verify($captcha_code, $key);
//            if ($res) {
//                TokenService::AddCacheKeyValue('captcha', '');
//            } else {
//                $data['error_code'] = 9;
//                $data['error_message'] = '验证码错误';
//                return $data;
//            }
        }

        $batch = null;
        $goods = null;
        $batch = null;
        $goods_id = null;
        $user = null;
        $is_sender_tip = '';
        $enterprise = null;

        $data['error_code'] = 0;
        $data['error_message'] = '';

        $code_row = CodeModel::where('code', $certify_code)->find();
        $data['code'] = $code_row;

        try {
            if (!$uid) {
                $uid = TokenService::getCurrentUid();
            }
        } catch (Throwable | Exception $e) {
            $data['error_code'] = 1;
            $data['error_message'] = '用户id不能为空';
            return $data;
        }
        $user = UserModel::where('id', $uid)->field('sender_name,id,is_sender,is_writeoff')->find();
        $data['user'] = $user;

        $first_certify_data = CodeResultModel::where('code', $certify_code)
            ->where('turn', 1)
            ->find();

        $my_certify_data = CodeResultModel::where('code', $certify_code)
            ->where('uid', $uid)
            ->find();

        $timestamp = time();
        $ip = Request()->ip();
        $insert_data = [
            'code' => $certify_code,
            'create_time' => $timestamp,
            'certify_time' => $timestamp,
            'goods_id' => 0,
            'status' => CODE_STATUS_NOTEXIST,
            'uid' => $uid,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'province' => $region[0],
            'city' => $region[1],
            'county' => $region[2],
            'ip' => $ip,
            'turn' => 0,
            'count' => 0
        ];
        $data['code_result'] = $insert_data;
        $enterprise = SysConfigModel::where('type',3)->column('value','key');

        if($code_row){
            $batch = $code_row->batch;
            if ($batch) {
                $goods_id = $batch->goods_id;
                if ($goods_id) {
                    $goods = GoodsModel::where('goods_id', $goods_id)->with(['imgs'])->find();
                    if ($goods) {
                        $goods->getBannerImgsList();
                    } else {
                        $data['error_code'] = 2;
                        $data['error_message'] = '批不到溯源码对应的商品';
                        return $data;
                    }
                } else {
                    $data['error_code'] = 3;
                    $data['error_message'] = '溯源码未绑定到商品';
                    return $data;
                }
            } else {
                $data['error_code'] = 4;
                $data['error_message'] = '溯源码未绑定到批次';
                return $data;
            }

        }

        $data['batch'] = $batch;
        $data['goods'] = $goods;
        $data['enterprise'] = $enterprise;

        $is_sender = $user['is_sender'];

        //查询最大次数
        $max_turn = CodeResultModel::where('code', $certify_code)->max('turn');

        if ($code_row) {//溯源码库中有码
            $insert_data['goods_id'] = $goods_id;
            if ($first_certify_data) {//有首查数据
                if($first_certify_data->uid == $uid){//是我首查且已查询过
                    $my_certify_data->count++;
                    $my_certify_data->certify_time = time();
                }else{
                    if($my_certify_data){ //我不是首查但查询过
                        $my_certify_data->count++;
                        $my_certify_data->certify_time = time();
                    }else{//我不是首查但未查询过
                        $insert_data['count'] = 1;
                        $insert_data['turn'] = $max_turn + 1;
                    }
                }
            } else {//无首查数据
                //未查询过,我是首查
                $insert_data['count'] = 1;
                $insert_data['turn'] = 1;
            }
        } else {//溯源码库中无码
            if ($my_certify_data) {//已查询过
                $my_certify_data->certify_time = time();
                $my_certify_data->count++;
            } else {//未查询过
                $insert_data['count'] = 1;
            }
        }
        if ($my_certify_data) {
            if (!$is_sender) {
                $my_certify_data->latitude = $latitude;
                $my_certify_data->longitude = $longitude;
                $my_certify_data->save();
            }
        } else {
            if (!$is_sender) {
                $my_certify_data = CodeResultModel::create($insert_data);
            } else {
                $my_certify_data = $insert_data;
                $is_sender_tip = '(您是发货员，查询信息不会保存，仅供参考)';
            }
        }
        $data['code_result'] = $my_certify_data;

        $data['company'] = SysConfigModel::where(['type' => 3])->field('value')->select();

        if ($first_certify_data) {
            $first_certify = $first_certify_data;
        } else {
            $first_certify = $my_certify_data;
        }

        $data['first_certify'] = $first_certify;
        $data['writeoff'] = CodeWriteoffModel::where('code', $certify_code)->find();

        //百度ip定位 优先使用经纬度查询
        if (!$region[0]) {
            $baidu_ak = SysConfigModel::where('key', 'baidu_map_server_ak')->value('value');
            if ($longitude) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $request = new \GuzzleHttp\Psr7\Request('get', "http://api.map.baidu.com/reverse_geocoding/v3/?ak={$baidu_ak}&output=json&coordtype=wgs84ll&location={$latitude},{$longitude}");
                    //$request = new \GuzzleHttp\Psr7\Request('get', 'http://auth.com/index.php/auth/soft_author?site_name='.$_SERVER['HTTP_HOST']);
                    $promise = $client->sendAsync($request)->then(function ($response) use ($user, $my_certify_data) {
                        $body = $response->getBody();
                        $stringBody = (string)$body;
                        $res = json_decode($stringBody, true);
                        if ($res['status'] == 0) {
                            $my_certify_data1['province'] = $res['result']['addressComponent']['province'];
                            $my_certify_data1['city'] = $res['result']['addressComponent']['city'];
                            $my_certify_data1['county'] = $res['result']['addressComponent']['district'];
                            if (isset($my_certify_data['id']) && $my_certify_data['id']) {
                                $res = CodeResultModel::where('id', $my_certify_data['id'])->update($my_certify_data1);
                            }
                            UserModel::where('id', $user['id'])->update([
                                'province' => $my_certify_data1['province'],
                                'city' => $my_certify_data1['city'],
                                'county' => $my_certify_data1['county']
                            ]);
                        } else {
                            Log::error($res);
                        }
                    });
                    $promise->wait();
                } catch (\Exception $e) {

                }
            } else {
                $ip = request()->ip();
                try {
                    $client = new \GuzzleHttp\Client();
                    $request = new \GuzzleHttp\Psr7\Request('get', "http://api.map.baidu.com/location/ip?ak={$baidu_ak}&ip={$ip}&coor=bd09ll");
                    //$request = new \GuzzleHttp\Psr7\Request('get', 'http://auth.com/index.php/auth/soft_author?site_name='.$_SERVER['HTTP_HOST']);
                    $promise = $client->sendAsync($request)->then(function ($response) use ($user, $my_certify_data) {
                        $body = $response->getBody();
                        $stringBody = (string)$body;
                        $res = json_decode($stringBody, true);
                        if ($res['status'] == 0) {
                            $my_certify_data1['province'] = $res['content']['address_detail']['province'];
                            $my_certify_data1['city'] = $res['content']['address_detail']['city'];
                            $cityId = Region::where('name', $my_certify_data1['city'])->value('id');
                            $countyName = Region::where('pid', $cityId)->value('name');
                            $my_certify_data1['county'] = $countyName;
                            if (isset($my_certify_data['id']) && $my_certify_data['id']) {
                                $res = CodeResultModel::where('id', $my_certify_data['id'])->update($my_certify_data1);
                            }
                            UserModel::where('id', $user['id'])->update([
                                'province' => $my_certify_data1['province'],
                                'city' => $my_certify_data1['city'],
                                'county' => $my_certify_data1['county']
                            ]);
                        } else {
                            Log::error($res);
                        }
                    });
                    $promise->wait();
                } catch (\Exception $e) {

                }
            }

        }

        //提示结果
        if ($my_certify_data) {
            //有码提示
            if ($goods) {
                if ($my_certify_data['turn'] == 1) {
                    if (!strip_tags($goods['genuine_tip'])) {
                        $result_tip = SysConfigModel::where('key', 'genuine_tip')->value('value');
                    }else{
                        $result_tip = $goods['genuine_tip'];
                    }
                } else {
                    if (!strip_tags($goods['counterfeit_tip'])) {
                        $result_tip = SysConfigModel::where('key', 'counterfeit_tip')->value('value');
                    }else{
                        $result_tip = $goods['counterfeit_tip'];
                    }
                }
            }else {
                //无码提示
                $result_tip = SysConfigModel::where('key', 'not_exist_tip')->value('value');
            }
        }

        //总查询次数
        $total_times = CodeResultModel::where('code', $certify_code)->sum('count');

        if($code_row){
            Code::where('code', $certify_code)->update([
                'scan_count' => $total_times
            ]);
        }

        $certify_time = $my_certify_data['certify_time'];
        $result_tip = str_replace('{{查询时间}}', date('Y年m月d日H点i分', strtotime($certify_time)), $result_tip);
        if ($enterprise) {
            $result_tip = str_replace('{{生产企业名称}}', $enterprise['company_name'], $result_tip);
        }
        if ($goods) {
            $result_tip = str_replace('{{商品名称}}', $goods['goods_name'], $result_tip);
        }
        $result_tip = str_replace('{{溯源码}}', $certify_code, $result_tip);

        $code_certify_total_times = CodeResultModel::where('code', $certify_code)->sum('count');
        $result_tip = str_replace('{{总查询次数}}', $code_certify_total_times, $result_tip);

        $result_tip = str_replace('{{客户查询名次}}', $my_certify_data['turn'], $result_tip);
        if ($first_certify) {
            $result_tip = str_replace('{{首次查询时间}}', $first_certify['certify_time'], $result_tip);
        }
        $data['result_tip'] = $result_tip . $is_sender_tip;

        return $data;
    }

    /**
     * 获取所有查询结果
     * @return mixed
     */
    public static function getAllList($page=1,$size=10,$uid='',$aid='',$search = null)
    {
        $obj = self::with(['goods'=>function($query){
            $query ->field('goods_id,goods_name');
        },'user'=>function($query){
            $query ->field('id,nickname,headpic');
        }]);

        if ($uid) {
            $obj->where('uid', $uid);
        }

        if (is_string($search) && $search !== '') {
            $decoded = json_decode($search, true);
            $search = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        if (!is_array($search)) {
            $search = [];
        }

        if (!empty($search['id'])) {
            $obj->where('id', (int) $search['id']);
        }
        if (!empty($search['goods_id'])) {
            $obj->where('goods_id', (int) $search['goods_id']);
        }
        if (!empty($search['code'])) {
            $obj->where('code', 'like', '%' . trim($search['code']) . '%');
        }
        if (!empty($search['goods_name'])) {
            $goodsIds = GoodsModel::where('goods_name', 'like', '%' . trim($search['goods_name']) . '%')->column('goods_id');
            $obj->whereIn('goods_id', $goodsIds ?: [0]);
        }
        if (!empty($search['nickname'])) {
            $uids = UserModel::where('nickname', 'like', '%' . trim($search['nickname']) . '%')->column('id');
            $obj->whereIn('uid', $uids ?: [0]);
        }
        if (!empty($search['ip'])) {
            $obj->where('ip', 'like', '%' . trim($search['ip']) . '%');
        }
        if (!empty($search['province'])) {
            $obj->where('province', 'like', '%' . trim($search['province']) . '%');
        }
        if (!empty($search['city'])) {
            $obj->where('city', 'like', '%' . trim($search['city']) . '%');
        }
        if (!empty($search['county'])) {
            $obj->where('county', 'like', '%' . trim($search['county']) . '%');
        }
        // 查询名次：first=首次 again=非首次 none=码不存在
        if (isset($search['turn_type']) && $search['turn_type'] !== '' && $search['turn_type'] !== null) {
            if ($search['turn_type'] === 'first') {
                $obj->where('turn', 1);
            } elseif ($search['turn_type'] === 'again') {
                $obj->where('turn', '>', 1);
            } elseif ($search['turn_type'] === 'none') {
                $obj->where(function ($q) {
                    $q->whereNull('turn')->whereOr('turn', 0);
                });
            }
        }
        if (!empty($search['date']) && is_array($search['date']) && count($search['date']) >= 2) {
            $start = (int) $search['date'][0];
            $end = (int) $search['date'][1];
            if ($start > 10000000000) {
                $start = (int) ($start / 1000);
            }
            if ($end > 10000000000) {
                $end = (int) ($end / 1000);
            }
            $obj->whereBetween('certify_time', [$start, $end]);
        }

        $total = $obj->count();
        $data = $obj->limit(($page - 1) * $size, $size)
            ->order('certify_time desc')
            ->select();

        $res = ['data' => $data, 'total' => $total];
        return $res;
    }

    public function goodsSku()
    {
        return $this->hasOne('GoodsSku', 'goods_id', 'goods_id');
    }
    public function getStatusDescAttr($value)
    {
        if(array_key_exists($value,self::$status)){
            return self::$status[$value];
        }else{
            return '状态未知';
        }

    }
    //关联商品
    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'goods_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }
    //关联溯源码
    public function codes()
    {
        return $this->belongsTo('Code', 'code', 'code');
    }
    public function codeItem()
    {
        return $this->belongsTo('Code', 'code', 'code');
    }
    public static function deleteByGoodsId($id)
    {
        $batch_ids = Batch::where('goods_id', $id)->column('id');
        $crIds = CodeResult::alias('cr')
            ->join('code c', 'c.code = cr.code')
            ->join('batch b', 'b.id=c.batch_id')
            ->whereIn('c.batch_id', $batch_ids)
            ->column('cr.id');
        CodeResult::whereIn('id',$crIds)->delete();
    }
}