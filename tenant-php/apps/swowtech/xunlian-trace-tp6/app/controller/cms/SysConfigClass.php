<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/4 0004
 * Time: 8:41
 */

namespace app\controller\cms;


use app\model\SysConfigClassEntity;
use bases\BaseController;


class SysConfigClass extends BaseController
{

    public function index($form = null)
    {
        $res = SysConfigClassEntity::order('order desc')->where('is_show', 1)->select();
        return app('json')->success($res);
    }
}