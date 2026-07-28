<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/30 0030
 * Time: 14:23
 */

namespace app\services;

use app\model\Batch;
use app\model\CodeResult as CodeResultModel;
use app\model\Goods as GoodsModel;
use app\model\Order as OrderModel;
use app\model\Region as RegionModel;
use app\model\User as UserModel;
use app\model\SysConfig as SysConfigModel;
use app\model\Code as CodeModel;
use app\model\CodeWriteoff as CodeWriteoffModel;

class StatisticService
{

    /**
     * 待处理事项统计
     * @return mixed
     */
    public static function remind()
    {
        $shipment = OrderModel::where(['state' => 0, 'payment_state' => 1, 'shipment_state' => 0])->field('count(order_id) as all_num')->find();
        $refund = OrderModel::where(['state' => -1, 'payment_state' => 1])->field('count(order_id) as all_num')->find();
        $goods_stock = GoodsModel::getGoodsStock();
        $data['shipment'] = $shipment['all_num'] ? $shipment['all_num'] : 0;
        $data['tui'] = $refund['all_num'] ? $refund['all_num'] : 0;
        $data['goods_stock'] = $goods_stock ? $goods_stock : 0;
        $data['total'] = 0;
        foreach ($data as $v) {
            $data['total'] += $v;
        }
        return app('json')->success($data);
    }

    /**
     * mcms订单统计
     * @return mixed
     */
    public static function getOrderNum()
    {
        $all = OrderModel::where(['state' => 1, 'payment_state' => 1])->field('count(order_id) as all_order,sum(order_money) as all_money')->find();
        $today = OrderModel::where(['state' => 1, 'payment_state' => 1])->whereDay('pay_time')->field('count(order_id) as today_order,sum(order_money) as today_money')->find();
        $yesterday = OrderModel::where(['state' => 1, 'payment_state' => 1])->whereDay('pay_time', 'yesterday')->field('count(order_id) as yesterday_order,sum(order_money) as yesterday_money')->find();
        $data['all_order'] = $all['all_order'] ? $all['all_order'] : 0;
        $data['all_money'] = $all['all_money'] ? $all['all_money'] : 0;
        $data['today_order'] = $today['today_order'] ? $today['today_order'] : 0;
        $data['today_money'] = $today['today_money'] ? $today['today_money'] : 0;
        $data['yesterday_order'] = $yesterday['yesterday_order'] ? $yesterday['yesterday_order'] : 0;
        $data['yesterday_money'] = $yesterday['yesterday_money'] ? $yesterday['yesterday_money'] : 0;
        return app('json')->success($data);
    }

    /**
     * cms首页数据统计
     * @return mixed
     */
    public static function getCmsIndexData()
    {
        $user = new UserModel();
        $code_result = new CodeResultModel();
//        $shipment = $order->where(['state' => 0, 'payment_state' => 1, 'shipment_state' => 0])->field('count(order_id) as all_num')->find();
//        $refund = $order->where(['state' => -1, 'payment_state' => 1])->field('count(order_id) as all_num')->find();
//        $goods_stock = GoodsModel::getGoodsStock();
//        $yesterday = $order->where(['state' => 1, 'payment_state' => 1])->whereDay('pay_time', 'yesterday')
//            ->field('count(order_id) as yesterday_order,sum(order_money) as yesterday_money')->find();
//        $month_order= $order->where(['state' => 1, 'payment_state' => 1])->whereMonth('pay_time')
//            ->field('count(order_id) as month_order,sum(order_money) as month_money')->find();

        $user_count = $user->field('count(id) as all_user')->find();

        $data['goods_count'] = GoodsModel::getGoodsCount();
        $data['search_count'] = $code_result->count();
        $data['today_search_count'] = $code_result->whereMonth('create_time')->count();
        $data['month_search_count'] = $code_result->whereMonth('create_time')->count();

        $data['user_count'] = $user->count();


        $data['alert_count'] = $code_result->where('turn', '<>', 1)->count();
        $data['month_alert_count'] = $code_result->whereMonth('create_time')->where('turn', '<>', 1)->count();
        $data['today_alert_count'] = $code_result->whereDay('create_time')->where('turn', '<>', 1)->count();

        return app('json')->success($data);
    }

    /**
     * cms图表统计
     * @return mixed
     */
    public static function getTableData()
    {
        $month = input('post.month');
        $time = self::getTime($month);
        $order = CodeResultModel::whereMonth('create_time', $time['time'])
            ->select();
        $user = userModel::whereMonth('create_time', $time['time'])
            ->select();
        for ($i = 0; strtotime($time['time']) < strtotime($time['last_time']); $i++) {
            if ($i == 0) {
                $time['time'] = date('Y-m-d', strtotime('+0 day', strtotime($time['time'])));
            }
            $data[$i]['day'] = $time['time'];
            $data[$i]['order_num'] = 0;
            $data[$i]['user_num'] = 0;
            $time['time'] = date('Y-m-d', strtotime('+1 day', strtotime($time['time'])));
        }
        foreach ($order as $k => $v) {
            $day = date('Y-m-d', strtotime($v['create_time']));
            foreach ($data as $key => $value) {
                if ($value['day'] == $day) {
                    $data[$key]['order_num'] += 1;
                }
            }
        }
        foreach ($user as $k => $v) {
            $day = date('Y-m-d', strtotime($v['create_time']));
            foreach ($data as $key => $value) {
                if ($value['day'] == $day) {
                    $data[$key]['user_num'] += 1;
                }
            }
        }
        return app('json')->success($data);
    }

    /**
     * 统计订单数据
     * @return mixed
     */
    public static function getOrderData()
    {
        $month = input('post.month');
        $time = self::getTime($month);
        $data['normal_order'] = 0;
        $data['discount_order'] = 0;
        $data['pt_order'] = 0;
        $order = OrderModel::where('payment_state', 1)
            ->where('state', '>=', '1')
            ->whereMonth('pay_time', $time['time'])
            ->select();

        foreach ($order as $k => $v) {
            if ($v['activity_type'] == '限时优惠') {
                $data['discount_order'] += 1;
            } else {
                $data['normal_order'] += 1;
            }
        }
        return app('json')->success($data);
    }

    public static function certifyAreaTop($post)
    {
        try {
            $qr = CodeResultModel::group('province,city,county')
                ->whereNotNull('province')
                ->field('province,city,county,count(code) as total_verify,sum(turn = \'1\') as turns,(count(code)-sum(turn != \'1\' OR NULL)) as total_counterfeit');

            if ($post['dispType'] == 'counterfeit') {
                $qr->order('total_counterfeit', 'desc');
            } else {
                $qr->order('turns', 'desc');
            }
            $total = $qr->count();
            $res = $qr->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
            $data = [
                'total' => $total,
                'data' => $res
            ];
        } catch (\Exception $e) {
            $vv = 0;
        }


        return app('json')->success($data);
    }

    /*
     * 区域表格查询统计
     */
    public static function certifyGoodsTop($post)
    {

        $qr = GoodsModel
            ::alias('g')
            ->with('imgs')
            ->join('xlsy_batch b', 'b.goods_id=g.goods_id')
            ->join('xlsy_code c', 'b.id=c.batch_id')
            ->join('xlsy_code_result r', 'r.code=c.code')
            ->group('g.goods_id')
            ->limit(($post['page'] - 1) * $post['size'], $post['size'])
            ->field('g.goods_id,g.goods_name,count(g.goods_id) as total_verify,sum(r.turn = \'1\') as turns,(count(g.goods_id)-sum(r.turn = \'1\' )) as total_counterfeit');
        $total = $qr->count();
        if ($post['dispType'] == 'counterfeit') {
            $qr->order('total_counterfeit', 'desc');
        } else {
            $qr->order('turns', 'desc');
        }
        $res = $qr->select()->toArray();

        foreach ($res as $k => &$v) {
            $batchIds = Batch::where('goods_id', $v['goods_id'])->column('id');
            $v['total_code'] = CodeModel::whereIn('batch_id', $batchIds)->count();
        }
        $data = [
            'total' => $total,
            'data' => $res
        ];

        return app('json')->success($data);
    }

    /**
     * 核销统计
     * @return mixed
     */
    public static function getWriteoffTotal()
    {
        $user = new UserModel();
//        $shipment = $order->where(['state' => 0, 'payment_state' => 1, 'shipment_state' => 0])->field('count(order_id) as all_num')->find();
//        $refund = $order->where(['state' => -1, 'payment_state' => 1])->field('count(order_id) as all_num')->find();
//        $goods_stock = GoodsModel::getGoodsStock();
//        $yesterday = $order->where(['state' => 1, 'payment_state' => 1])->whereDay('pay_time', 'yesterday')
//            ->field('count(order_id) as yesterday_order,sum(order_money) as yesterday_money')->find();
//        $month_order= $order->where(['state' => 1, 'payment_state' => 1])->whereMonth('pay_time')
//            ->field('count(order_id) as month_order,sum(order_money) as month_money')->find();

        $user_count = $user->field('count(id) as all_user')->find();

        $data['goods_count'] = GoodsModel::getGoodsCount();;
        $data['code_count'] = CodeModel::count();
        $data['writeoff_count'] = CodeWriteoffModel::count();
        $data['not_writeoff_count'] = $data['code_count'] - CodeWriteoffModel::count();

        return app('json')->success($data);
    }

    /*
     * 表格核销统计
     */
    public static function getWriteoffTable($month)
    {

        if (!$month) {
            $date = time();
        } else {
            $date = strtotime(date('Y-m-d', $month / 1000));
        }

        //月第一天

        $day_str = date('Y-m', $date) . '-01 0:0:0';
        $day_month_start = strtotime($day_str);

        //月最后一天
        $day_month_end = strtotime(date("Y-m-d", strtotime("$day_str +1 month -1 day")) . ' 23:59:59');

        //取每页数据条数的天数
        for ($i = 0; $i < 31; $i++) {

            $day = date('Y-m-d', strtotime("- {$i} days", $day_month_end));

            if (strtotime($day) < $day_month_start) {
                break;
            }

            $count_date[$i]['start_time'] = strtotime($day . ' 0:0:0');
            $count_date[$i]['end_time'] = strtotime($day . ' 23:59:59');

            $data[$i]['date'] = $day;

            $total = CodeWriteoffModel::whereBetweenTime('create_time', $count_date[$i]['start_time'], $count_date[$i]['end_time'])->count();
            $data[$i]['total'] = $total;
        }

        $res = [
            'data' => $data
        ];

        return app('json')->success($res);
    }

    /**
     * 省份预警数据
     * @return mixed
     */
    public static function getAreaVerifyData($month = '', $dispType = '')
    {
        $data = [];

        //省份查询数据
        $qr = CodeResultModel::group('province')
            ->whereNotNull('province')
            ->field('province,count(*) as total_verify,sum(turn = \'1\' ) as turns,(count(*)-sum(turn <> \'1\' OR NULL)) as total_counterfeit');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $qr->whereMonth('certify_time', $month);
        }
        if ($dispType == 'counterfeit') {
            $qr->order('total_counterfeit', 'desc');
        } else {
            $qr->order('turns', 'desc');
        }
        $res = $qr->select();
        foreach ($res as $k => &$v) {

            $v['province'] = RegionModel::where('name', $v['province'])->value('shortname') ?? $v['province'];
            if (!$v['province']) {
                $v['province'] = '未知';
            }
        }
        $data['province'] = $res;

        //县查询数据
        $res1 = CodeResultModel::group('county')
            ->whereNotNull('county')
            ->field('county,count(*) as total_verify,sum(turn = \'1\' OR NULL) as turns,(count(*)-sum(turn = \'1\' OR NULL)) as total_counterfeit');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res1->whereMonth('certify_time', $month);
        }
        $res1 = $res1->select();
        foreach ($res1 as $k => &$v) {
            if (!$v['county']) {
                $v['county'] = '未知';
            }
        }
        $data['county'] = $res1;

        return app('json')->success($data);
    }

    /**
     * 单商品地区统计图表
     * @return mixed
     */
    public static function getAreaByOneGoods($month = '', $dispType = null, $id = null)
    {
        $data = [];
        $res = CodeResultModel::group('r.province')
            ->alias('r')
            ->join('xlsy_code c', 'c.code=r.code')
            ->join('xlsy_batch b', 'c.batch_id=b.id')
            ->join('xlsy_goods g', 'g.goods_id=b.goods_id')
            ->limit(10)
            //->whereNotNull('province')
            ->where('g.goods_id', $id)
            ->field('r.province,count(g.goods_id) as total');
        if ($dispType != 'total') {
            if ($dispType == 'counterfeit') {
                $res->where('r.turn', '<>', '1');
            } else {
                $res->where('r.turn', '1');
            }
        }

        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('r.certify_time', $month);
        }
        $data = $res->select();

        return app('json')->success($data);
    }

    /**
     * 地区单商品统计图表
     * @return mixed
     */
    public static function getGoodsByArea($month = '', $dispType = null, $area = null)
    {
        $data = [];
        $res = CodeResultModel
            ::alias('r')
            ->join('xlsy_code c', 'c.code=r.code')
            ->join('xlsy_batch b', 'c.batch_id=b.id')
            ->join('xlsy_goods g', 'g.goods_id=b.goods_id')
            ->group('g.goods_id')
            ->limit(10)
            //->whereNotNull('province')
            ->where('r.province', $area);
        if ($dispType != 'total') {
            if ($dispType != 'counterfeit') {
                $res = $res->where('r.turn', '1')
                    ->field('g.goods_name,count(*) as turns');

            } else {
                $res = $res->where('r.turn', '<>', '1')
                    ->field('g.goods_name,count(*) as turns');
            }
        } else {
            $res = $res->field('g.goods_name,count(*) as turns');
        }

        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('r.certify_time', $month);
        }
        $data = $res->select();

        return app('json')->success($data);
    }

    /**
     * 省份预警数据
     * @return mixed
     */
    public static function getProductVerifyData($month = '', $dispType = '')
    {
        $data = [];

        // 商品关联走批次：code.goods_id 常为空，不能直接用码表 goods_id
        $res = CodeResultModel
            ::alias('c')
            ->join('code cd', 'cd.code=c.code')
            ->join('batch b', 'b.id=cd.batch_id')
            ->join('goods g', 'g.goods_id=IFNULL(NULLIF(c.goods_id,0), IFNULL(NULLIF(cd.goods_id,0), b.goods_id))')
            ->group('g.goods_id')
            ->limit(10)
            ->field('g.goods_id,g.goods_name,count(c.id) as total,sum(c.turn = \'1\' ) as turns,(count(c.id)-sum(c.turn = \'1\')) as total_counterfeit');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('c.certify_time', $month);
        }
        if ($dispType == 'counterfeit') {
            $res->order('total_counterfeit', 'desc');
        } else {
            $res->order('turns', 'desc');
        }
        $res = $res->select()->toArray();
        $data['goods'] = $res;

        //按商品分类的总查询次数和首查查询次数
        $res = CodeResultModel
            ::alias('c')
            ->join('code cd', 'cd.code=c.code')
            ->join('batch b', 'b.id=cd.batch_id')
            ->join('goods g', 'g.goods_id=IFNULL(NULLIF(c.goods_id,0), IFNULL(NULLIF(cd.goods_id,0), b.goods_id))')
            ->join('category t', 't.id=g.category_id')
            ->group('g.category_id')
            ->limit(10)
            ->field('t.name,t.id,count(c.id) as total,sum(c.turn = \'1\') as turns,(count(c.id)-sum(c.turn = \'1\' )) as total_counterfeit');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('c.certify_time', $month);
        }
        if ($dispType == 'counterfeit') {
            $res->order('total_counterfeit', 'desc');
        } else {
            $res->order('turns', 'desc');
        }

        $res = $res->select()->toArray();
        $data['goods_type'] = $res;

        return app('json')->success($data);
    }

    /**
     * 省份预警数据
     * @return mixed
     */
    public static function getWarningDataProvince($month = '')
    {
        $data = [];
        $res = CodeResultModel::group('province')
            ->where('turn', '<>', '1')
            ->whereNotNull('province')
            ->field('province,count(*) as value');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('certify_time', $month);
        }
        $res = $res->select();
        foreach ($res as $k => $v) {
            $pro_res[$v['province']] = $v['value'];
        }
        $author = SysConfigModel::where('key', 'author_code')->value('desc');

        $province_data = RegionModel::where('level', 1)->column('shortname', 'name');
        foreach ($province_data as $k => $v) {
            $data[] = [
                'name' => $v,
                'value' => $author == '已授权' ? (isset($pro_res[$k]) ? $pro_res[$k] : 0) : rand(0, 200)
            ];
        }

        return app('json')->success($data);
    }

    /**
     * 省份预警数据
     * @return mixed
     */
    public static function getWarningDataProvinceTooltip($month = '')
    {
        $data = [];
        $tool_tip_data = [];
        $res = CodeResultModel::group('province')
            ->where('turn', '<>', '1')
            ->whereNotNull('province')
            ->field('province,count(*) as value');
        if ($month) {
            $month = date("Y-m-d", ($month / 1000));
            $res->whereMonth('certify_time', $month);
        }
        $res = $res->select();
        foreach ($res as $k => $v) {
            $pro_res[$v['province']] = $v['value'];
        }

        $province_data = RegionModel::where('level', 1)->column('shortname', 'name');

        $author = SysConfigModel::where('key', 'author_code')->value('desc');

        foreach ($province_data as $k => $v) {
            $data1 = [];
            $data = [
                'name' => '查询',
                'value' => $author == '已授权' ? (isset($pro_res[$k]) ? $pro_res[$k] : 0) : rand(0, 200)
            ];
            $data1[] = $data;
            $tool_tip_data[] = [
                'name' => $v,
                'value' => $data1
            ];

        }

        return app('json')->success($tool_tip_data);
    }

    /**
     * cms图表统计订单
     * @return mixed
     */
    public static function getMoneyData()
    {
        $month = input('post.month');
        $time = self::getTime($month);
        $order = OrderModel::where('payment_state', 1)
            ->where('state', '>=', '1')
            ->whereMonth('pay_time', $time['time'])
            ->select();
        for ($i = 0; strtotime($time['time']) < strtotime($time['last_time']); $i++) {
            $data[$i]['day'] = $time['time'];
            $data[$i]['total_price'] = 0;
            $data[$i]['total_order'] = 0;
            $data[$i]['profit'] = 0;
            $time['time'] = date('Y-m-d', strtotime('+1 day', strtotime($time['time'])));
        }
        foreach ($order as $k => $v) {
            $day = strtotime(date('Y-m-d', $v['pay_time']));
            foreach ($data as $key => $value) {
                if (strtotime($value['day']) == $day) {
                    $data[$key]['total_price'] += $v['goods_money'];
                    $data[$key]['total_order'] += 1;
                }
            }
        }
        if (app('system')->getValue('is_pt') == 1) {
            foreach ($pt_order as $k => $v) {
                $day = strtotime(date('Y-m-d', $v['pay_time']));
                foreach ($data as $key => $value) {
                    if (strtotime($value['day']) == $day) {
                        $data[$key]['total_price'] += $v['goods_money'];
                        $data[$key]['total_order'] += 1;
                    }
                }
            }
        }
        return app('json')->success($data);
    }

    private static function getTime($month)
    {
        if ($month) {
            $start = date('Y-m', $month);
            $end = date('Y-m', strtotime('+1 month', $month));
        } else {
            $start = date('Y-m', time());
            $end = date('Y-m', strtotime('+1 month', time()));
        }

        $date['time'] = $start;
        $date['last_time'] = $end;
        return $date;
    }

    /**
     * 查询统计概览：KPI、近30天趋势、异常单码
     */
    public static function getCertifyOverview($month = '', $dispType = 'total')
    {
        $goodsJoin = 'g.goods_id=IFNULL(NULLIF(c.goods_id,0), IFNULL(NULLIF(cd.goods_id,0), b.goods_id))';

        $scope = function ($query) use ($month, $dispType) {
            if ($month) {
                $query->whereMonth('c.certify_time', date('Y-m-d', (int)($month / 1000)));
            }
            if ($dispType === 'genuine') {
                $query->where('c.turn', 1);
            } elseif ($dispType === 'counterfeit') {
                $query->where('c.turn', '<>', 1)->where('c.turn', '<>', 0);
            }
        };

        $kpiBase = CodeResultModel::alias('c');
        $scope($kpiBase);
        $kpiRow = $kpiBase
            ->field('count(c.id) as records, sum(c.count) as query_total, sum(c.turn = 1) as first_count, sum(c.turn <> 1 AND c.turn <> 0) as counterfeit_count, count(distinct c.code) as code_count')
            ->find();

        $todayBase = CodeResultModel::alias('c')->whereDay('c.certify_time');
        $scope($todayBase);
        $todayRow = $todayBase->field('count(c.id) as records, sum(c.count) as query_total')->find();

        $monthBase = CodeResultModel::alias('c')->whereMonth('c.certify_time');
        if (!$month) {
            $monthBase->whereMonth('c.certify_time', date('Y-m-d'));
        }
        $scope($monthBase);
        $monthRow = $monthBase->field('count(c.id) as records, sum(c.count) as query_total')->find();

        $data['kpi'] = [
            'records' => (int)($kpiRow['records'] ?? 0),
            'query_total' => (int)($kpiRow['query_total'] ?? 0),
            'first_count' => (int)($kpiRow['first_count'] ?? 0),
            'counterfeit_count' => (int)($kpiRow['counterfeit_count'] ?? 0),
            'code_count' => (int)($kpiRow['code_count'] ?? 0),
            'today_records' => (int)($todayRow['records'] ?? 0),
            'today_query_total' => (int)($todayRow['query_total'] ?? 0),
            'month_records' => (int)($monthRow['records'] ?? 0),
            'month_query_total' => (int)($monthRow['query_total'] ?? 0),
        ];

        $trend = [];
        $startDay = strtotime(date('Y-m-d', strtotime('-29 days')));
        for ($i = 0; $i < 30; $i++) {
            $day = date('Y-m-d', strtotime("+{$i} day", $startDay));
            $dayStart = strtotime($day);
            $dayEnd = strtotime($day . ' 23:59:59');
            $row = CodeResultModel::whereBetween('certify_time', [$dayStart, $dayEnd])
                ->field('count(id) as total, sum(turn = 1) as first, sum(turn <> 1 AND turn <> 0) as counterfeit')
                ->find();
            $trend[] = [
                'day' => $day,
                'total' => (int)($row['total'] ?? 0),
                'first' => (int)($row['first'] ?? 0),
                'counterfeit' => (int)($row['counterfeit'] ?? 0),
            ];
        }
        $data['trend'] = $trend;

        $data['abnormal'] = [];
        if ($dispType !== 'genuine') {
            $abnormalQuery = CodeResultModel::alias('c')
                ->join('code cd', 'cd.code=c.code')
                ->join('batch b', 'b.id=cd.batch_id')
                ->join('goods g', $goodsJoin)
                ->field('c.code,g.goods_name,sum(c.count) as query_total,max(c.turn) as max_turn,count(distinct c.uid) as user_count,max(c.certify_time) as last_certify_time')
                ->group('c.code,g.goods_name');
            $scope($abnormalQuery);
            if ($dispType === 'counterfeit') {
                $abnormalQuery->having('max_turn > 1');
            } else {
                $abnormalQuery->having('max_turn > 1 OR sum(c.count) > 1 OR count(distinct c.uid) > 1');
            }
            $data['abnormal'] = $abnormalQuery
                ->order('query_total', 'desc')
                ->limit(20)
                ->select()
                ->toArray();
        }

        return app('json')->success($data);
    }

    /**
     * 分销佣金统计排名
     * @return mixed
     */
    public static function countFx()
    {
        $month = input('post.month');
        $time = self::getTime($month);
        $data = FxRecord::with(['agent.user', 'user'])->whereMonth('create_time', $time['time'])->select();
        $arr = [];
        $res = [];
        foreach ($data as $k => $v) {
            if (!$arr) {
                $arr['agent_id'] = $v['agent_id'];
                $arr['num'] = 1;
                $arr['all_money'] = $v['money'];
                $arr['nickname'] = $v['agent']['user']['nickname'];
                $arr['headpic'] = $v['agent']['user']['headpic'];
                if ($v['status'] == 0) {
                    $arr['money'] = $v['money'];
                } else {
                    $arr['money'] = 0;
                }
                array_push($res, $arr);
                continue;
            }
            foreach ($res as $kk => $vv) {
                if ($v['agent_id'] == $vv['agent_id']) {
                    $res[$kk]['num'] += 1;
                    $res[$kk]['all_money'] += $v['money'];
                    if ($v['status'] == 0) {
                        $res[$kk]['money'] += $v['money'];
                    }
                    break;
                }
                if ($kk + 1 == count($res)) {
                    $arr['agent_id'] = $v['agent_id'];
                    $arr['num'] = 1;
                    $arr['all_money'] = $v['money'];
                    $arr['nickname'] = $v['agent']['user']['nickname'];
                    $arr['headpic'] = $v['agent']['user']['headpic'];
                    if ($v['status'] == 0) {
                        $arr['money'] = $v['money'];
                    } else {
                        $arr['money'] = 0;
                    }
                    array_push($res, $arr);
                }
            }
        }
        for ($i = 0; $i < count($res); $i++) {
            for ($j = $i; $j < count($res); $j++) {
                if ($res[$i]['num'] < $res[$j]['num']) {
                    $t = $res[$j];
                    $res[$j] = $res[$i];
                    $res[$i] = $t;
                }
            }
        }
        return app('json')->success($res);
    }

}