<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 8:52
 */

namespace app\model;


use bases\BaseModel;

class SysConfigClassEntity extends BaseModel
{
    /** 不含库前缀；前缀由 database.php 按 X-Tenant-Id 动态拼为 cy_{id}_xlsy_ */
    protected $name = 'sys_config_class';
}