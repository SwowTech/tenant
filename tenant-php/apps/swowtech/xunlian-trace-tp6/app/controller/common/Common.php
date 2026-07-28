<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 15:31
 */

namespace app\controller\common;

use app\model\Batch;
use app\model\Code;
use app\model\CodeResult;
use app\model\Goods;
use bases\BaseController;
use exceptions\TipException;
use utils\Captcha;

class Common extends BaseController
{
    public function refreshItem($id, $name)
    {
        switch ($name) {
            case 'product.code_count':
            {
                $code_count = Code::where('goods_id', $id)->count();
                Goods::where('goods_id', $id)->update(['code_count' => $code_count]);
                return app('json')->success($code_count);
                break;
            }
            case 'product.scan_count':
            {
                $scan_count = CodeResult::where('goods_id', $id)->count();
                Goods::where('goods_id', $id)->update(['scan_count' => $scan_count]);
                return app('json')->success($scan_count);
                break;
            }
            case 'product.batch_count':
            {
                $batch_count = Batch::where('goods_id', $id)->count();
                Goods::where('goods_id', $id)->update(['batch_count' => $batch_count]);
                return app('json')->success($batch_count);
                break;
            }
            case 'code.scan_count':
            {
                $code = Code::where('id', $id)->find();
                $scan_count = CodeResult::where('code', $code['code'])->sum('count');
                Code::update(['scan_count' => $scan_count], ["id" => $id]);
                return app('json')->success($scan_count);
                break;
            }
            case 'batch.code_count':
            {
                $code_count = Code::where('batch_id', $id)->count();
                Batch::update(['code_count' => $code_count], ["id" => $id]);
                return app('json')->success($code_count);
                break;
            }
            //进程状态
//            case 'daemon_process.status':
//            {
//                $status = app(DaemonProcessServices::class)->getRunningStatus($id);
//                return app('json')->success($status);
//                break;
//            }
        }
        throw new TipException("找不到匹配项");
    }


    /**
     * 操作进度
     * @param $request
     * @return \think\response\Json
     */
    public function operationProgress($operation = 'export_code_progress')
    {

        $stop = request()->param('command');
        if ($stop) {
            $session['command'] = 'stop';
            $progressData = setSessionProcessMsg($operation, $session);
        } else {
            $progressData = getSessionProcessMsg($operation);
        }

        return app('json')->success($progressData);
    }


    /**
     * 验证码
     * @return $this|\think\Response
     */
    public function captchaPro()
    {
        return app()->make(Captcha::class)->create();
    }
}