<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/4 0004
 * Time: 8:41
 */

namespace app\controller\cms;


use app\model\User as UserModel;
use bases\BaseController;
use app\model\CodeWriteoff as CodeWriteoffModel;
use app\model\CodeResult as CodeResultModel;

class UserManage extends BaseController
{
    /**
     * 获取所有用户信息
     * @return mixed
     */
    public function getAllUser($page=1,$size=10,$search=null){
       return UserModel::getAllUser($page,$size,$search);
    }
    /**
     * 改变用户禁用状态
     * @return mixed
     */
    public function revertBan($id,$state){
        return UserModel::revertBan($id,$state);
    }
    /**
     * 删除粉丝用
     * @return mixed
     */
    public function deleteUser($id){
         $res = UserModel::destroy($id);

         CodeResultModel::where('uid',$id)->delete();
         CodeWriteoffModel::where('uid',$id)->delete();

         if($res){
             return app('json')->success();
         }
        return app('json')->fail();
    }
    /**
     * 编辑粉丝
     * @return mixed
     */
    public function editUser($edit_form){
        $res = UserModel::update($edit_form,['id'=>$edit_form['id']]);

        if($res){
            return app('json')->success();
        }
        return app('json')->fail();
    }
    /**
     * 改变用户核销员状态
     * @return mixed
     */
    public function revertWriteoff($id,$state){
        return UserModel::revertWriteoff($id,$state);
    }
    /**
     * 改变用户发货员状态
     * @return mixed
     */
    public function revertSender($id,$state){
        return UserModel::revertSender($id,$state);
    }
}