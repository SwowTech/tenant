<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/29 0029
 * Time: 14:19
 */

namespace app\controller\cms;


use app\model\SysBackup as SysBackupModel;
use backup\Backsql;
use bases\BaseController;
use think\facade\Config;
use think\facade\Db;

class Backup extends BaseController
{
    /**
     * 添加备份数据库
     * @return mixed
     */
    public function addBackup()
    {

        $sql = new Backsql(Config::get("database"));
        return $sql->backup();
    }


    /**
     * 获取备份列表
     * @return mixed
     */
    public function getBackup(){
        $res=SysBackupModel::order('id','desc')->select();
        return app('json')->success($res);
    }

    /**
     * 删除备份
     * @param $id
     * @return mixed
     */
    public function deleteBackup($id){
        $item = SysBackupModel::where('id',$id)->find();
        try{
            $res=unlink(get_public_path().$item['url']);
            if( $res ){
                $item->delete();
            }
        }catch(\Exception $e){
            $msg = strtolower($e->getMessage());
            if(strstr($msg,'no such')){
                $item->delete();
            }else{
                return app('json')->fail('删除文件失败');
            }

        }
        return app('json')->success();
    }
}