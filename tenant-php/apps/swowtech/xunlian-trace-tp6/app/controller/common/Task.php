<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 15:50
 */

namespace app\controller\common;


use app\services\TaskService;
use bases\BaseController;

class Task extends BaseController
{

    public function getRefresh()
    {
        return (new TaskService())->DayTask();
    }
}