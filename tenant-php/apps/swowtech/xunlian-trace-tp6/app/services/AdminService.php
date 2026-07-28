<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/14 0014
 * Time: 14:38
 */

namespace app\services;


use app\model\Admin as AdminModel;
use app\model\Group as GroupModel;
use enum\ScopeEnum;
use stdClass;

class AdminService extends TokenService
{
    /**
     * @var 实例句柄
     */
    private static $instance;

    /**
     * 获取实例句柄
     * @return AdminService
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public static function getUserInfo()
    {
        $uid = TokenService::getCurrentAid();
        $user = AdminModel::find($uid);
        if (!$user) {
            return app('json')->fail('找不到用户');
        }
        $oauthRaw = GroupModel::where('id', $user->group_id)->value('oauth');
        $oauth = array_values(array_filter(explode(',', (string) $oauthRaw)));
        if (!$oauth) {
            $oauth = ['admin'];
        }
        $info = $user->toArray();
        unset($info['password'], $info['mcid']);
        return [
            'name'  => $user['username'],
            'info'  => $info,
            'oauth' => $oauth,
            'roles' => $oauth,
        ];
    }
    /**
     * 注册管理员
     * @param $post
     * @return mixed
     */
    public function register($post)
    {
        $user = AdminModel::where('username', $post['username'])->find();
        if ($user) {
            return app('json')->fail('用户名已存在');
        }
        $data['username'] = $post['username'];
        $data['password'] = password($post['password']);
        $data['group_id'] = 2;
        $data['create_time'] = time();
        if (array_key_exists('description', $post)) {
            $data['description'] = $post['description'];
        }
        $res = AdminModel::create($data);
        if ($res) {
            return app('json')->success($res['id']);
        } else {
            return app('json')->fail();
        }
    }

    /**
     * 登录，并返回token
     * @param $user
     * @param $pwd
     * @return \think\response\Json
     */
    public function loginService($user, $pwd)
    {
        // $password = password($pwd);    //common文件的函数
        $password = $pwd;
        $where['username'] = $user;
        $where['password'] = password($password);
        $user = AdminModel::where($where)->find();
        if (!$user) {
            return app('json')->fail('账号或密码错误');
        }
        if ($user->state == 1) {
            return app('json')->fail('已禁用');
        }
        $cachedValue = $this->setWxCache($user);//仅组合
        $res['token'] = $this->saveCache($cachedValue);
        $oauth = GroupModel::where('id', $user->group_id)->value('oauth');
        $res['oauth'] = explode(',', $oauth);
        return app('json')->success($res);
//        return json($res);
    }
    /**
     * 组合uid，username，权限
     * @param $user
     * @return mixed
     */
    private function setWxCache($user)
    {
        $cache['admin_id'] = $user->id;
        $cache['username'] = $user->username;
        $cache['uid'] = $user->id;
        $cache['scope'] = ScopeEnum::Root;  // scope=16 代表App用户的权限数值
        return $cache;
    }

    /**
     * 修改密码
     * @param $form
     * @return mixed
     */
    public function editAdminPwd($form)
    {
        $aid = TokenService::getCurrentAid();
        $admin = AdminModel::find($aid);
        if (!$admin) {
            return app('json')->fail('用户信息错误');
        }
        if ($admin->password != password($form['old_psw'])) {
            return app('json')->fail('原密码错误');
        }
        $password = password($form['new_psw']);
        $res = AdminModel::update(['password' => $password, 'id' => $aid]);
        //$admin->where('id',$aid)->save();
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
    }

    /**
     * 修改管理员信息
     * @param $form
     * @return mixed
     */
    public function editAdmin($form)
    {
        $aid = TokenService::getCurrentAid();
        if($aid!=1){
            return app('json')->fail('没有权限');
        }
        $id = $form['id'];
        if(!$form['username']){
            $data['group_id'] = 2;
        }
        $data['password'] = password($form['password']);
        if (array_key_exists('description', $form)) {
            $data['description'] = $form['description'];
        }
        $res = AdminModel::where('id', $id)->update($data);
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
//        return $res ? 1 : 0;
    }

    /**
     * 获取所有管理员
     * @return \think\Collection
     */
    public function getAllAdmin($page=1,$size=10,$search=null)
    {
        $aid = TokenService::getCurrentAid();
        if($aid!=1){
            return app('json')->fail('没有权限');
        }
        $aid = TokenService::getCurrentAid();
        $data = [];
        $search = json_decode($search,true);
        if($aid == 1){
            $query = AdminModel::order('id','desc');
            if($search && $search['username']){
                $query->where('username','like','%'.$search['username'].'%');
            }
            $data = $query->paginate($size);

        }
         return app('json')->success($data);
    }

    /**
     * 删除管理员
     * @param $id
     * @return mixed
     */
    public function deleteAdmin($id){
        $aid = TokenService::getCurrentAid();
        if($aid!=1){
            return app('json')->fail('没有权限');
        }
        if ($id <= 1) {
            return app('json')->auth_err('不能删除最高管理员');
        }
        $res=AdminModel::where('id',$id)->delete();
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
//        return $res?1:0;
    }

    /**
     * 禁用管理员
     * @param $id
     * @return mixed
     */
    public function banAdmin($id){
        $aid = TokenService::getCurrentAid();
        if($aid!=1){
            return app('json')->fail('没有权限');
        }
        if ($id <= 1) {
            return app('json')->auth_err('不能禁用最高管理员');
        }
        $state=AdminModel::where('id',$id)->value('state');
        if($state==1){
            $res=AdminModel::where('id',$id)->update(['state'=> 0]);

        }else{
            $res=AdminModel::where('id',$id)->update(['state'=> 1]);
        }
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
    //        return $res?1:0;
    }
}