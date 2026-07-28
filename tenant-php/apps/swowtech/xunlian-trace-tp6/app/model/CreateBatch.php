<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/14 0014
 * Time: 15:49
 */

namespace app\model;


use app\model\Code as CodeModel;
use app\model\CodeResult as CodeResultModel;
use app\model\CodeWriteoff as CodeWriteoffModel;
use app\services\TokenService;
use bases\BaseModel;

class CreateBatch extends BaseModel
{
    /**
     *
     * @return int
     */
    public static function addBatch($post)
    {
        $data['name'] = $post['name'];
        $data['description'] = $post['description'];
        $data['create_time'] = time();
        $res = self::create($data);
        if ($res) {
            return app('json')->success($res['id']);
        } else {
            return app('json')->fail();
        }
    }
    public static function deleteCreateBatch($id)
    {

        $res = self::destroy($id);
        if ($res) {
            return app('json')->success();
        } else {
            return app('json')->fail();
        }
    }
    /**
     *
     * @param $post
     * @return int
     */
    public static function getBatchList($page = 1, $size = 10, $search = '')
    {
        $query = self::order('id', 'desc');
        if (isset($post['search'])) {
            if ($post['search']['date']) {
                $query = $query->where('create_time', '>=', $post['search']['date'][0] / 1000);
                $query = $query->where('create_time', '<=', $post['search']['date'][1] / 1000);
            }
            if ($post['search']['name']) {
                $query = $query->where('name', 'like', '%' . $post['search']['name'] . '%');
            }
        }
        $res = $query->paginate($size);
        $res->each(function ($item, $key) {
            $item['code_count'] = Code::where('create_batch_id', $item['id'])->count();
        });
        if ($res) {
            return app('json')->success($res);
        } else {
            return app('json')->fail();
        }
    }

    public static function getCodesByCreateBatchId($id, $page, $size, $search,$type)
    {
        $ids_min_max_not_used = [];
        $ids_min_max_used = [];
        $batch_ids = Code::where('create_batch_id', $id)->whereNotNull('batch_id')->distinct(true)->field('batch_id')->select()->toArray();
        if($type == 'not_used'){
            $idsNotUsed = Code::where('create_batch_id', $id)->whereNull('batch_id')->order('id', 'asc')->column('id');
            if ($idsNotUsed) {
                $line = 0;
                $ids_min_max_not_used[$line]['min'] = $idsNotUsed[0];
                $min = $idsNotUsed[0];
                $lineIndex = 1;
                for ($i = 1; $i < count($idsNotUsed); $i++) {
                    if ($idsNotUsed[$i] != $ids_min_max_not_used[$line]['min'] + $lineIndex++) {
                        $ids_min_max_not_used[$line]['max'] = $idsNotUsed[$i-1];
                        $ids_min_max_not_used[$line]['batch_count'] = 0;
                        $ids_min_max_not_used[$line]['code_count'] = $ids_min_max_not_used[$line]['max'] - $ids_min_max_not_used[$line]['min'] + 1;
                        $line++;
                        $lineIndex = 1;
                        $ids_min_max_not_used[$line]['min'] = $idsNotUsed[$i];
                    }
                }
                $ids_min_max_not_used[$line]['max'] = $idsNotUsed[$i-1];
                $ids_min_max_not_used[$line]['batch_count'] = 0;
                $ids_min_max_not_used[$line]['code_count'] = $ids_min_max_not_used[$line]['max'] - $ids_min_max_not_used[$line]['min'] + 1;

            }
            $res = $ids_min_max_not_used;

        }else{
            $ids = Code::where('create_batch_id', $id)->whereNotNull('batch_id')->order('id', 'asc')->column('id');
            if ($ids) {
                $line = 0;
                $ids_min_max_used[$line]['min'] = $ids[0];
                $min = $ids[0];
                $lineIndex = 1;
                for ($i = 1; $i < count($ids); $i++) {
                    if ($ids[$i] != $ids_min_max_used[$line]['min'] + $lineIndex++) {
                        $ids_min_max_used[$line]['max'] = $ids[$i-1];
                        $batchIdItems = Code::whereBetween('id', $ids_min_max_used[$line]['min'].','.$ids_min_max_used[$line]['max'])
                            ->distinct(true)->field('batch_id')
                            ->select();
                        $ids_min_max_used[$line]['batch_count'] = count($batchIdItems);
                        $ids_min_max_used[$line]['code_count'] = $ids_min_max_used[$line]['max'] - $ids_min_max_used[$line]['min'] + 1;
                        $line++;
                        $lineIndex = 1;
                        $ids_min_max_used[$line]['min'] = $ids[$i];
                    }
                }
                $ids_min_max_used[$line]['max'] = $ids[$i-1];
                $batchIdItems = Code::whereBetween('id', $ids_min_max_used[$line]['min'].','.$ids_min_max_used[$line]['max'])
                    ->distinct(true)->field('batch_id')
                    ->select();
                $ids_min_max_used[$line]['batch_count'] = count($batchIdItems);
                $ids_min_max_used[$line]['code_count'] = $ids_min_max_used[$line]['max'] - $ids_min_max_used[$line]['min'] + 1;
            }
            $res = $ids_min_max_used;
        }

        if ($res) {
            return app('json')->success($res);
        } else {
            return app('json')->fail();
        }
    }

    public static function editBatch($id, $batch)
    {
        $data = $batch;
        $res = self::where('id', $id)->update($data);
        if ($res) {
            return app('json')->success();
        } else {
            return app('json')->fail();
        }
    }
    public static function codeAssignProgress()
    {
        if ((session_status() != PHP_SESSION_ACTIVE)) {
            session_start();
        }
        $now_percent = @$_SESSION['code_assign_progress'];
        session_write_close();
        return app('json')->success($now_percent);
    }
    public static function codeAssign($operation,$start_id,$end_id,$batch_id)
    {
        static $line = 0;
        $aid = TokenService::getCurrentAid();

        $total = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id)->count();
        $obj = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id);

        $session = ['count' => 0, 'total' => $total, 'msg' => "开始", 'act' => 'starting'];
        setSessionProcessMsg('code_assign_progress', $session);
        $over = false;
        $data = $obj->field('id,code')->chunk(500, function ($codes) use ( $batch_id,$aid, $operation, $total, $over, &$line) {

            $line += count($codes);

            $codes = $codes->column('code');
            if ($operation == 'assign') {
                $res2 = CodeModel::whereIn('code', $codes)->update(['batch_id'=>$batch_id]);
            }else{
                $res2 = CodeModel::whereIn('code', $codes)->update(['batch_id'=>null]);
            }
            if ((session_status() != PHP_SESSION_ACTIVE)) {
                session_start();
            }
            $_SESSION['code_assign_progress'] = ['count' => $line, 'total' => $total, 'msg' => "已处理{$line}条", 'act' => 'doing'];
            session_write_close();  //释放session锁

        });

        if ((session_status() != PHP_SESSION_ACTIVE)) {
            session_start();
        }
        $_SESSION['code_assign_progress'] = [
            'total' => $total,
            'count' => $line,
            'msg' => '完成',
            'act' => 'over',
        ];
        session_write_close();  //释放session锁


        $ret = [
            'count' => $line,
            'total' => $total,
            'errcode' => 0
        ];
        return app('json')->success($ret);
    }

}