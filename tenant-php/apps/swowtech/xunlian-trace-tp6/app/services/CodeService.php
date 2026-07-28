<?php

namespace app\services;
use app\model\CodeResult as codeResultModel;
use app\model\CodeWriteoff as CodeWriteoffModel;
class CodeService
{
    public static function getCodeResult($type,$page=1,$size=10){
        $uid = TokenService::getCurrentUid();
        $total = 0;
        switch($type){
            case 'all':{
                $data = codeResultModel::where('uid',$uid)
                    ->with(['goods' => function($query){
                        $query->field('goods_id,goods_name');
                    }])
                    ->order('create_time','desc')
                    ->limit(($page-1)*$size,$size)
                    ->select();


                $total = codeResultModel::where('uid',$uid)->count();
                break;
            }
            case 'genuine':{
                $data = codeResultModel::where('uid',$uid)
                    ->with(['goods' => function($query){
                        $query->field('goods_id,goods_name');
                    },'codes'=>function($query){
                        $query->field('id,code');
                    }
                    ])
                    ->where('turn',1)
                    // ->whereRaw(' code in (select code from xlsy_code) ')
                    ->order('create_time','desc')
                    ->limit(($page-1)*$size,$size)
                    ->select();

                $total = codeResultModel::where('uid',$uid)
                    ->where('turn',1)
                    ->count();

                foreach ($data as $k => $v){
                    if(!$v['codes']){
                        unset($data[$k]);
                    }
                }

                break;
            }
            case 'counterfeit':{
                $data = codeResultModel
                    ::with(['goods' => function($query){
                        $query->field('goods_id,goods_name');
                    },'codes'=>function($query){
                        $query->field('id,code');
                    }
                    ])
                    ->where('turn','<>',1)
//                    ->whereRaw(' code in (select code from xlsy_code) ')
//                    ->whereOr(function($query) use ($uid){
//                        $query->whereRaw(' code not in (select code from xlsy_code) ')
//                            ->where('uid',$uid);
//                    })
                    ->where('uid',$uid)
                    ->order('create_time','desc')
                    //->limit(($page-1)*$size,$size)
                    ->select();

                $total = codeResultModel
                    ::where('turn','<>',1)
                    ->where('uid',$uid)
                    ->count();
                break;
            }
            case 'statistics':
            {
                $data['total'] = codeResultModel::where('uid', $uid)->count();
                $data['genuine'] = codeResultModel::where('uid', $uid)
                    ->where('turn', 1)
                    ->whereRaw(' code in (select code from xlsy_code) ')
                    ->count();
                $data['counterfeit'] = codeResultModel::where('uid', $uid)
                    ->where('turn', '<>', 1)
                    ->whereRaw(' code in (select code from xlsy_code) ')
                    ->whereOr(function ($query) use ($uid) {
                        $query->whereRaw(' code not in (select code from xlsy_code) ')
                            ->where('uid', $uid);
                    })
                    ->count();
            }
        }

        $res['data'] = $data;
        $res['total'] = $total;

        return $res;

    }
    //核销员核销结果
    public static function getWriteoffResult($type,$page=1,$size=10){
        $uid = TokenService::getCurrentUid();
        $total = 0;
        switch($type){
            case 'all':{
                $data = CodeWriteoffModel::where('uid',$uid)
                    ->with(['goods' => function($query){
                        $query->field('goods_id,goods_name');
                        }])
                    ->order('create_time','desc')
                    ->limit(($page-1)*$size,$size)
                    ->select();


                $total = CodeWriteoffModel::where('uid',$uid)->count();
                break;
            }
            case 'statistics':{
                $data['total'] = CodeWriteoffModel::where('uid',$uid)->count();

                $today_start = strtotime(getTodayStart());
                $today_end = strtotime(getTodayEnd());

                $month_start = strtotime(getMonthStart());
                $month_end =   strtotime(getMonthEnd());

                $data['today'] = CodeWriteoffModel::where('uid',$uid)->whereBetweenTime('create_time',$today_start,$today_end)->count();
                $data['month'] = CodeWriteoffModel::where('uid',$uid)->whereBetweenTime('create_time',$month_start,$month_end)->count();
            }
        }

        $res['data'] = $data;
        $res['total'] = $total;

        return $res;

    }
}