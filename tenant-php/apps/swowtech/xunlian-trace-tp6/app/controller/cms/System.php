<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/17 0017
 * Time: 8:52
 */

namespace app\controller\cms;


use app\model\SysConfig as SysConfigModel;
use app\model\Template;
use app\services\AuthService;
use app\services\AutoUpdate;
use bases\BaseController;
use think\Exception;

class System extends BaseController
{
    /** 公开接口禁止返回的敏感配置 key 片段 */
    private static $sensitiveKeyNeedles = [
        'secret',
        'password',
        'aes_key',
        'server_ak',
        'author_code',
        'gzh_token',
        'access_token',
    ];

    /**
     * 登录页等公开场景允许返回的配置
     */
    private static $publicConfigKeys = [
        'site_name',
        'login_logo',
        'login_bg_pic',
        'square_logo',
        'login_logo_square',
        'copyright_info',
        'h5_url',
        'captcha_code_show',
        'miniapp_logo',
        'qrcode_logo',
        'mayanImage',
        'navigate_show',
        'share_name',
        'top_image',
        'bottom_image',
        'correct_image',
        'error_image',
        'noticeImage',
        'scanImage',
        'userBackImage',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'is_send',
        'is_writeoff',
        'is_writeoff_operator',
    ];

    private function isSensitiveConfigKey($key)
    {
        $key = strtolower((string) $key);
        foreach (self::$sensitiveKeyNeedles as $needle) {
            if ($needle !== '' && strpos($key, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isCmsAdminRequest()
    {
        try {
            $token = request()->header('token');
            if (!$token || $token === 'null') {
                return false;
            }
            $vars = cache($token);
            if (!$vars) {
                return false;
            }
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            return !empty($vars['admin_id']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function filterConfigMap(array $data)
    {
        if ($this->isCmsAdminRequest()) {
            return $data;
        }
        $filtered = [];
        foreach ($data as $k => $v) {
            if ($this->isSensitiveConfigKey($k)) {
                continue;
            }
            if (!in_array($k, self::$publicConfigKeys, true)) {
                continue;
            }
            $filtered[$k] = $v;
        }
        return $filtered;
    }

    /**
     * 获取商城配置信息
     * @param $type
     * @return \think\response\Json
     */
    public function getConfig($type='',$key='')
    {
        if($key){
            if (!$this->isCmsAdminRequest() && $this->isSensitiveConfigKey($key)) {
                return app('json')->fail('无权获取该配置');
            }
            if (!$this->isCmsAdminRequest() && !in_array($key, self::$publicConfigKeys, true)) {
                return app('json')->fail('无权获取该配置');
            }
            $data = SysConfigModel::where('key',$key)->find();
            return app('json')->success('操作成功',$data);
        }else{
            $data = SysConfigModel::where(['type'=>$type])->order('switch','desc')->select();
            if (!$this->isCmsAdminRequest()) {
                $data = $data->filter(function ($row) {
                    $k = $row['key'] ?? '';
                    return !$this->isSensitiveConfigKey($k) && in_array($k, self::$publicConfigKeys, true);
                })->values();
            }
            foreach($data as $k => $v){
                if($v['key'] =='miniapp_logo'){
                    $v['value'] = buildImageFullPath($v['value']);
                }
            }
            return app('json')->success($data);
        }
    }

    public function getConfigValue($type='',$key='')
    {
        $key = $key !== '' && $key !== null ? $key : input('key', '');
        $keys = input('keys', '');
        $type = ($type !== '' && $type !== null) ? $type : input('type', '');

        if ($keys && is_string($keys)) {
            $key = array_values(array_filter(array_map('trim', explode(',', $keys))));
        }

        if($key){
            if(is_array($key)){
                $allow = [];
                foreach ($key as $k) {
                    if ($this->isSensitiveConfigKey($k)) {
                        continue;
                    }
                    if (!$this->isCmsAdminRequest() && !in_array($k, self::$publicConfigKeys, true)) {
                        continue;
                    }
                    $allow[] = $k;
                }
                $data = $allow
                    ? SysConfigModel::whereIn('key', $allow)->column('value', 'key')
                    : [];
                return app('json')->success($data);
            }
            if ($this->isSensitiveConfigKey($key) && !$this->isCmsAdminRequest()) {
                return app('json')->fail('无权获取该配置');
            }
            if (!$this->isCmsAdminRequest() && !in_array($key, self::$publicConfigKeys, true)) {
                return app('json')->fail('无权获取该配置');
            }
            $data = SysConfigModel::where('key',$key)->value('value');
            return app('json')->success('操作成功',$data);
        }else{
            $data = SysConfigModel::where(['type'=>$type])->order('switch','desc')->column('value','key');
            $data = $this->filterConfigMap(is_array($data) ? $data : []);
            return app('json')->success($data);
        }
    }
    /**
     * 修改配置信息
     * @return int
     */
    public function editConfig()
    {
        $post = input('post.');
        foreach ($post as $k => $v){
            $v['update_time'] = time();
            $v['create_time'] = strtotime($v['create_time']);
            // 授权码：空值直接未授权；有值先保存再校验
            if (($v['key'] ?? '') === 'author_code') {
                $code = trim((string) ($v['value'] ?? ''));
                if ($code === '') {
                    $v['value'] = '';
                    $v['desc'] = '未授权';
                }
            }
            $res =SysConfigModel::where('id',$v['id'])->update($v);
            if (($v['key'] ?? '') === 'author_code' && trim((string) ($v['value'] ?? '')) !== '') {
                AuthService::auth();
            }
        }
        return app('json')->success();
    }

    /**
     * 获取当前授权状态（不自动拉码）
     */
    public function getAuthStatus()
    {
        return app('json')->success(AuthService::current());
    }

    /**
     * 检查本地授权码
     */
    public function checkAuth()
    {
        return app('json')->success(AuthService::check());
    }

    /**
     * 从授权服务器获取授权码
     */
    public function fetchAuth()
    {
        return app('json')->success(AuthService::fetch());
    }

    /**
     * 修改模板消息
     * @return mixed
     */
    public function editTemplate(){
        $post=input('post.');
       return Template::editTemplate($post);
    }
    public function clearCache(){
        $path = app()->getRuntimePath();
        $data = delDirAndFile($path);
        return app('json')->success($data);
    }
    public function systemUpdate(){
        try {
            return AutoUpdate::instance()->update();
        }catch (Exception $exception){
            return app('json')->fail($exception->getMessage());
        }
    }
    public function getSystemVersion(){
        try {
            AuthService::auth();
        } catch (\Throwable $e) {
            // 授权服务器异常不影响版本检查页
        }

        $orginalVer = ORIGANAL_VERSION;
        $orginalDesc = ORIGANAL_DESC;
        $versionInfo = SysConfigModel::where('key', 'version')->field('value,desc')->find();
        if (!$versionInfo) {
            $dataBase= \think\facade\Config::get('database');
            $mysqlConfig = $dataBase['connections']['mysql'];
            $sql = <<<SQL
            INSERT INTO `{$mysqlConfig['database']}`.`xlsy_sys_config`( `key`, `value`, `desc`, `type`, `switch`, `update_time`, `create_time`, `is_show`) VALUES ( 'version', '$orginalVer', '$orginalDesc', 1, 0, NULL, NULL, 0);
SQL;
            \think\facade\Db::execute($sql);
            $versionInfo = SysConfigModel::where('key', 'version')->field('value,desc')->find();
        }

        $data = [
            'versionInfo' => $versionInfo ? $versionInfo->toArray() : [
                'value' => $orginalVer,
                'desc' => $orginalDesc,
            ],
            'updateInfo' => [],
            'server_reachable' => false,
            'server_msg' => '',
        ];

        $ver = $data['versionInfo']['value'] ?? $orginalVer;
        $httpCode = 0;
        $raw = curl_get(VENDOR_URL . '/service/get_update_info?version=' . urlencode((string) $ver), $httpCode);
        if ($raw === false || $raw === null || $raw === '') {
            $data['server_msg'] = '更新服务器无法连接，请稍后再试';
            return app('json')->success($data);
        }

        $updateInfo = json_decode($raw, true);
        if (is_array($updateInfo) && (int) ($updateInfo['status'] ?? 0) === 200) {
            $data['updateInfo'] = $updateInfo['data'] ?? [];
            $data['server_reachable'] = true;
            $data['server_msg'] = '';
            return app('json')->success($data);
        }

        $data['server_msg'] = is_array($updateInfo) && !empty($updateInfo['msg'])
            ? (string) $updateInfo['msg']
            : '获取更新信息失败（更新服务器异常）';
        return app('json')->success($data);
    }
}
