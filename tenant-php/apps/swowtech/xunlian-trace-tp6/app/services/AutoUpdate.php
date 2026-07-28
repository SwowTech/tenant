<?php

namespace app\services;

use app\model\SysConfig;
use think\Exception;
use think\facade\Config;
use think\facade\Db;
use ZipArchive;

class AutoUpdate
{
    public $version = ORIGANAL_VERSION;
    private $vendor_url = VENDOR_URL;
    /**
     * @var AutoUpdate 对象实例
     */
    protected static $instance;

    public function __construct($version = null)
    {
        if (!is_null($version)) {
            $this->version = $version;
        }
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return AutoUpdate
     */
    public static function instance($version = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($version);
        }

        return self::$instance;
    }


    /**
     * 解压文件
     *
     * @param string $name 文件名
     * @return  string
     * @throws  Exception
     */
    public function unzip($name,$path)
    {
        $appDir = $path;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($name) !== TRUE) {
                throw new Exception('无法打开ZIP文件');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                @copy("zip://" . $name . "#" . $filename, $appDir . $filename);
            }
            $zip->close();
            return $appDir;
        }
        throw new Exception("无法执行解压操作，请确保ZipArchive安装正确");
    }

    public function checkVersion()
    {
        $ver = SysConfig::where('key', 'version')->value('value');
        return $ver;
    }

    /**
     * 执行升级
     * @param int $type
     * @return string
     * @throws Exception
     */
    public function update($type = 2)
    {
        try {
            $old_version = $this->checkVersion();
            $httpCode = 0;
            $updateInfo = curl_get($this->vendor_url . '/service/get_update_info?version=' . urlencode((string) $old_version), $httpCode);
            if ($updateInfo === false || $updateInfo === null || $updateInfo === '') {
                throw new \Exception('更新服务器无法连接，请稍后再试');
            }
            $updateInfo = json_decode($updateInfo, true);
            if (!is_array($updateInfo) || (int) ($updateInfo['status'] ?? 0) != 200) {
                throw new \Exception(is_array($updateInfo) && !empty($updateInfo['msg']) ? $updateInfo['msg'] : '获取更新信息失败');
            }
            $temPath = runtime_path() . 'update';
            if (!is_dir($temPath)) {
                mkdir($temPath);
            }
            if(!$updateInfo['data']){
                return app('json')->fail('已是最新版');
            }
            foreach ($updateInfo['data'] as $index => $updateItem) {

                $fileUrl = $updateItem['file'];
                $updateFile = curl_get($fileUrl);

                $filename = $updateItem['product'] . '_' . $updateItem['version'] . '.zip';

                $temPath = rtrim($temPath, "/") . "/";
                $fp2 = @fopen($temPath . $filename, 'a');
                fwrite($fp2, $updateFile);
                fclose($fp2);

                $fileType = FileTypeCheck::getFileType($temPath . $filename);

                //判断下载的数据 是否为空 下载超时问题
                if ($fileType != "zip") {
                    throw new \Exception("不是zip文件！");
                }
                $appPath = root_path();
                $this->unzip($temPath . $filename,$appPath);
                $updateFile = $appPath . 'update.php';
                if (file_exists($temPath)) {
                    require_once($updateFile);
                }
                SysConfig::where('key','version')
                    ->update([
                        'value'=>$updateItem['product'].'_'.$updateItem['version'],
                        'desc'=>$updateItem['desc']
                    ]);
                delDirAndFile($temPath);
                unlink($appPath.'/update.php');
            }
            return app('json')->success('更新成功');
        } catch (Exception $ex) {
            return app('json')->fail($ex->getMessage());
        }
    }

}
