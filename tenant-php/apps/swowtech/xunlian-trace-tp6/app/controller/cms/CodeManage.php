<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 10:06
 */

namespace app\controller\cms;


use app\controller\common\MiniWechat;
use app\model\Batch;
use app\model\CreateBatch;
use app\model\Goods;
use app\model\Goods as GoodsModel;
use app\model\Code as CodeModel;
use app\model\SysConfig;
use app\model\SysConfig as SysConfigModel;
use app\services\TokenService;
use app\validate\CodeValidate;
use bases\BaseController;
use app\services\CodeService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Exception\GenerateImageException;
use exceptions\TipException;
use think\facade\Db;
use think\facade\Log;
use app\model\Batch as BatchModel;
use Throwable;
use Exception;
use app\model\CodeResult as CodeResultModel;
use app\model\CodeWriteoff as CodeWriteoffModel;

class CodeManage extends BaseController
{

    /**
     * cms 商品溯源码查询情况
     */
    public function getResult($code)
    {
        $validate = new CodeValidate();
        $validate->goCheck();
        $post = $validate->getDataByRule(input('post.'));
        return CodeModel::getResult($post);
    }

    /**
     * cms 删除 商品溯源码查询情况
     */
    public function deleteCodeCertifyResult($id)
    {
        if ($id) {
            $res = CodeResultModel::destroy($id);
            if ($res) {
                return app('json')->success();
            }
        }
        return app('json')->fail();
    }

    /**
     * 生成溯源码
     * @param $request
     * @return \think\response\Json
     */
    public function createCode(\app\Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        $filename = 'create_qrcode';
        define('PHP_LOCK_FILE', runtime_path() . "{$filename}.lock");
        define('LOCK_TIP', '已有任务运行中，请等待完成!');
        try {
            require __DIR__ . '/../../lock.php';

        } catch (Exception $e) {
            throw new TipException('已有任务运行中，请等待完成');
        }
        $goods = [];
        $batch = [];
        $goodsId = null;

        $session = getSessionProcessMsg('create_code_progress');
        $session['command'] = 'doing';
        setSessionProcessMsg('create_code_progress', $session);

        $batch_data = [];
        $uid = TokenService::getCurrentAid();
        $create_time = time();
        $create_form = $request->param('create_form');
        $create_batch_id = $request->param('create_batch_id');
        //获取授权码
        $val = SysConfigModel::where('key', 'author_code')->value('desc');

        if ($val != '已授权') {
            $key = file_get_contents(app()->getAppPath() . 'rsa/public.key');
            $public_key = openssl_pkey_get_public($key);
            $return_de = openssl_public_decrypt(base64_decode($val), $decrypted, $public_key);
            if ($return_de && $decrypted == $_SERVER['HTTP_HOST']) {
                //已授权处理
                SysConfigModel::where('key', 'author_code')->update(['desc' => '已授权']);

            } else {
                //未授权处理
                SysConfigModel::where('key', 'author_code')->update(['desc' => '未授权']);
            }
        }

        $number = 2 + 98 + 900;
        try {

            //批量生成
            if ($create_form['batch_type'] == 2) {
                if (!$create_form['goods_id']) {
                    throw new TipException('商品不能为空');
                }

                $goodsId = (int) $create_form['goods_id'];
                $goods = Goods::where('goods_id', $goodsId)->field('goods_id,goods_name')->find();
                if (!$goods) {
                    throw new TipException('商品不存在');
                }
                $batch = [];
                $batch_count = (int) ($create_form['batch_count'] ?? 0);
                if ($batch_count <= 0) {
                    throw new TipException('批次数为0');
                }

                $prefix = trim((string) ($create_form['batch_prefix'] ?? ''));
                // 计算批次号：前缀 + 当天日期 + 6位序号，如 ABC20250725000001
                $today = date('Ymd', $create_time);
                $namePrefix = $prefix . $today;
                $batch_exists = BatchModel::where('name', 'like', $namePrefix . '%')->column('name');
                $max = 0;
                foreach ($batch_exists as $existName) {
                    if (preg_match('/^' . preg_quote($namePrefix, '/') . '(\d{6})$/', (string) $existName, $m)) {
                        $max = max($max, (int) $m[1]);
                    }
                }

                $batch_names = [];
                $batch_insert_data = [];
                for ($i = 0; $i < $batch_count; $i++) {
                    $max++;
                    $batchName = $namePrefix . str_pad((string) $max, 6, '0', STR_PAD_LEFT);
                    // name 字段最长 40
                    if (mb_strlen($batchName) > 40) {
                        throw new TipException('批次名称过长，请缩短名称前缀');
                    }
                    $batch_names[] = $batchName;
                    $batch_insert_data[] = [
                        'name' => $batchName,
                        'description' => ($prefix !== '' ? $prefix . '-' : '') . $today . '批量生成' . ($i + 1),
                        'goods_id' => $goodsId,
                        'create_time' => $create_time,
                        'mcid' => 1,
                        'code_count' => 0,
                        'diyContent' => '[]',
                    ];
                }
                BatchModel::insertAll($batch_insert_data);
                // 按批次名精确取回刚创建的批次，避免仅靠 create_time 漏取
                $batch_data = BatchModel::whereIn('name', $batch_names)
                    ->where('goods_id', $goodsId)
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
                if (count($batch_data) !== $batch_count) {
                    throw new TipException('批量创建批次失败，请重试');
                }
            } else {//单批次生成
                if (!$create_form['batch_id']) {
                    throw new TipException('批次不能为空');
                }
                $batch_id = $create_form['batch_id'];
                $batch = Batch::where('id', $batch_id)->find();
                $goodsId = $batch['goods_id'];
                $goods = Goods::where('goods_id', $goodsId)->field('goods_id,goods_name')->find();
                if ($batch_id) {
                    $batch_data = BatchModel::where('id', $batch_id)->select()->toArray();
                } else {
                    throw new TipException('批次id不能为空');
                }
            }

            //生成规则
            if ($create_form['code_rule'] == 1) {//字母数字组合
                //不区分大小写
                $pattern = '';
                if ($create_form['case_rule'] == 1) {
                    $pattern = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
                } else {
                    $pattern = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                }
                $pattern .= '1234567890';
            }
            if ($create_form['code_rule'] == 2) {//纯字母
                //不区分大小写
                $pattern = '';
                if ($create_form['case_rule'] == 1) {
                    $pattern = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
                } else {
                    $pattern = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                }
            }
            if ($create_form['code_rule'] == 3) {//纯数字
                $pattern = '1234567890';
            }
            $insert_data = [];
            $insert_count = 0;    //插入计数
            $count = max(1, (int) ($create_form['count'] ?? 0));// 每个批次生成数量
            $insert_count_real = 0;
            $insert_count_batch = 0;
            $author = SysConfigModel::where('key', 'author_code')->value('desc');

            if (empty($batch_data) || !is_array($batch_data)) {
                throw new TipException('没有可生成的批次');
            }

            // 未授权仅限制可生成数量，不能跳过真正的插入逻辑
            if ($author != '已授权') {
                $hascount = CodeModel::count();
                if ($hascount >= $number) {
                    $session_value = ['count' => 0, 'total' => 0, 'msg' => '产品未授权', 'act' => 'error', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                    setSessionProcessMsg('create_code_progress', $session_value);
                    throw new TipException('产品未授权');
                }
                $remain = $number - $hascount;
                $batchNum = max(1, count($batch_data));
                $maxPerBatch = (int) floor($remain / $batchNum);
                if ($maxPerBatch < 1) {
                    $session_value = ['count' => 0, 'total' => 0, 'msg' => '产品未授权', 'act' => 'error', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                    setSessionProcessMsg('create_code_progress', $session_value);
                    throw new TipException('产品未授权');
                }
                if ($count > $maxPerBatch) {
                    $count = $maxPerBatch;
                }
            }

            $progressTotal = $count * max(1, count($batch_data) ?: 1);
            $session_value = [
                'count' => 0,
                'total' => $progressTotal,
                'msg' => '正在生成溯源码',
                'act' => 'doing',
                'command' => 'doing',
                'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]
            ];
            setSessionProcessMsg('create_code_progress', $session_value);

            if ($batch_data) {
                foreach ($batch_data as $batchKey => $value) {
                    $insert_count_batch = 0;

                    for ($i = 1; $i <= $count; $i++) {
                        $command = getSessionProcessMsg('create_code_progress');
                        if (isset($command['command']) && $command['command'] == 'stop') {
                            $session_value = ['count' => $insert_count, 'total' => $progressTotal, 'msg' => '用户停止', 'act' => 'over', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                            setSessionProcessMsg('create_code_progress', $session_value);
                            throw new TipException('用户停止');
                        }
                        $key = '';
                        $insert_count++;
                        for ($j = 0; $j < $create_form['number_of_letters']; $j++) {
                            $key .= $pattern[mt_rand(0, strlen($pattern) - 1)];    //生成php随机数
                        }
                        $insert_data[] = [
                            'code' => $key,
                            'batch_id' => $value['id'],
                            'create_time' => $create_time,
                            'mcid' => $uid ?: 1,
                            'goods_id' => $goodsId,
                        ];
                        if ($i % 1000 == 0 || $i == $count) {
                            if (count($insert_data) > 0) {
                                $inserted = CodeModel::insertAll($insert_data);
                                $insert_count_batch += $inserted;
                                $insert_count_real += $inserted;
                                $insert_data = [];
                            }
                        }
                        if ($i % 100 == 0 || $i == $count) {
                            $session_value = ['count' => $insert_count, 'total' => $progressTotal, 'msg' => '正在生成溯源码', 'act' => 'doing', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                            setSessionProcessMsg('create_code_progress', $session_value);
                        }
                    }
                    if ($insert_count_batch < $count) {
                        for ($k = 0; $k < 10; $k++) {
                            $create_count = $count - $insert_count_batch;
                            if ($create_count <= 0) {
                                break;
                            }
                            for ($i = 1; $i <= $create_count; $i++) {
                                $key = '';
                                for ($j = 0; $j < $create_form['number_of_letters']; $j++) {
                                    $key .= $pattern[mt_rand(0, strlen($pattern) - 1)];
                                }
                                $insert_data[] = [
                                    'code' => $key,
                                    'batch_id' => $value['id'],
                                    'create_time' => $create_time,
                                    'goods_id' => $goodsId,
                                    'mcid' => $uid ?: 1,
                                ];
                                if ($i % 1000 == 0 || $i == $create_count) {
                                    if (count($insert_data) > 0) {
                                        $inserted = CodeModel::insertAll($insert_data);
                                        $insert_count_batch += $inserted;
                                        $insert_count_real += $inserted;
                                        $insert_data = [];
                                    }
                                }
                            }
                        }
                    }
                    Batch::where('id', $value['id'])->update(['code_count' => Db::raw('code_count+' . $insert_count_batch)]);
                    if ($create_form['batch_type'] == 2 && $batchKey == count($batch_data) - 1) {
                        Goods::where('goods_id', $goodsId)->update(['code_count' => Db::raw('code_count+' . $insert_count_real)]);
                        Goods::where('goods_id', $goodsId)->update(['batch_count' => Db::raw('batch_count+' . count($batch_data))]);
                    } elseif ($create_form['batch_type'] != 2) {
                        Goods::where('goods_id', $goodsId)->update(['code_count' => Db::raw('code_count+' . $insert_count_batch)]);
                    }
                }

            } else {//生成批次生成溯源码
                for ($i = 1; $i <= $count; $i++) {

                    $command = getSessionProcessMsg('create_code_progress');
                    if (isset($command['command']) && $command['command'] == 'stop') {
                        $session_value = ['count' => $insert_count, 'total' => $progressTotal, 'msg' => '用户停止', 'act' => 'over', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                        setSessionProcessMsg('create_code_progress', $session_value);
                        throw new TipException('用户停止');
                    }

                    $key = '';
                    $insert_count++;
                    for ($j = 0; $j < $create_form['number_of_letters']; $j++) {
                        $key .= $pattern[mt_rand(0, strlen($pattern) - 1)];
                    }
                    $insert_data[] = [
                        'code' => $key,
                        'create_time' => $create_time,
                        'create_batch_id' => $create_batch_id,
                        'goods_id' => $goodsId,
                        'mcid' => $uid ?: 1,
                    ];
                    if ($i % 1000 == 0 || $i == $count) {
                        if (count($insert_data) > 0) {
                            $insert_count_real += CodeModel::insertAll($insert_data);
                            $insert_data = [];
                        }
                    }
                    if ($i % 100 == 0 || $i == $count) {
                        $session_value = ['count' => $insert_count, 'total' => $progressTotal, 'msg' => '正在生成溯源码', 'act' => 'doing', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
                        setSessionProcessMsg('create_code_progress', $session_value);
                    }
                }
            }
            $res = ['insert_count' => $insert_count_real];
            $session_value = ['count' => $insert_count_real, 'total' => $progressTotal, 'act' => 'over', 'msg' => '插入成功', 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
            setSessionProcessMsg('create_code_progress', $session_value);
            return app('json')->success('插入成功', $res);
        } catch (TipException $e) {
            throw $e;
        } catch (\Exception $e) {
            $session_value = ['act' => 'error', 'msg' => '插入出错:' . $e->getMessage(), 'task' => ['create_form' => $create_form, 'batch' => $batch, 'goods' => $goods]];
            setSessionProcessMsg('create_code_progress', $session_value);
            throw new TipException('插入出错:' . $e->getMessage());
        }

    }

    /*
     * 导出目录浏览：type=qrcode|xls，path=相对根目录的子路径
    */
    public function listExportFiles($type = 'qrcode', $path = '')
    {
        $resolved = $this->resolveExportRoot((string)$type);
        if (!$resolved) {
            return app('json')->fail('未知目录类型');
        }
        $safePath = $this->safeRelativePath($path);
        $absDir = $this->joinPath($resolved['abs'], $safePath);
        if (!is_dir($absDir) || !$this->isPathInside($resolved['abs'], $absDir)) {
            return app('json')->fail('目录不存在');
        }

        $publicUrl = get_public_url();
        $files = [];
        $totalSize = 0;
        foreach (scandir($absDir) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $filePath = $absDir . DIRECTORY_SEPARATOR . $name;
            $relItem = $safePath === '' ? $name : ($safePath . '/' . $name);
            $mtime = @filemtime($filePath) ?: 0;
            if (is_dir($filePath)) {
                $size = $this->dirSize($filePath);
                $totalSize += $size;
                $files[] = [
                    'name' => $name,
                    'rel' => $relItem,
                    'kind' => 'dir',
                    'kind_text' => '文件夹',
                    'type' => $resolved['key'],
                    'size' => $size,
                    'size_text' => $this->formatFileSize($size),
                    'mtime' => $mtime,
                    'mtime_text' => $mtime ? date('Y-m-d H:i:s', $mtime) : '',
                    'url' => '',
                    'can_download' => false,
                    'can_enter' => true,
                ];
                continue;
            }
            if (!is_file($filePath)) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $size = filesize($filePath);
            $totalSize += $size;
            $canDownload = false;
            $url = '';
            if ($resolved['key'] === 'qrcode' && $ext === 'zip') {
                $canDownload = true;
                $url = $publicUrl . '/' . $resolved['rel'] . '/' . $relItem;
            } elseif ($resolved['key'] === 'xls' && in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
                $canDownload = true;
                $url = $publicUrl . '/' . $resolved['rel'] . '/' . $relItem;
            } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                // 二维码图片也可下载/打开
                $canDownload = true;
                $url = $publicUrl . '/' . $resolved['rel'] . '/' . $relItem;
            }
            $files[] = [
                'name' => $name,
                'rel' => $relItem,
                'kind' => 'file',
                'kind_text' => strtoupper($ext ?: 'FILE'),
                'type' => $resolved['key'],
                'size' => $size,
                'size_text' => $this->formatFileSize($size),
                'mtime' => $mtime,
                'mtime_text' => $mtime ? date('Y-m-d H:i:s', $mtime) : '',
                'url' => $url,
                'can_download' => $canDownload,
                'can_enter' => false,
            ];
        }

        usort($files, function ($a, $b) {
            if (($a['kind'] ?? '') !== ($b['kind'] ?? '')) {
                return ($a['kind'] === 'dir') ? -1 : 1;
            }
            return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
        });

        $parent = null;
        if ($safePath !== '') {
            $parts = explode('/', $safePath);
            array_pop($parts);
            $parent = implode('/', $parts);
        }

        return app('json')->success([
            'key' => $resolved['key'],
            'title' => $resolved['title'],
            'root' => $resolved['rel'],
            'path' => $safePath,
            'display_path' => $safePath === '' ? $resolved['rel'] : ($resolved['rel'] . '/' . $safePath),
            'parent' => $parent,
            'count' => count($files),
            'size' => $totalSize,
            'size_text' => $this->formatFileSize($totalSize),
            'files' => $files,
        ]);
    }

    private function resolveExportRoot($type)
    {
        $publicPath = get_public_path();
        if ($type === 'qrcode') {
            $uid = TokenService::getCurrentAid();
            $rel = 'code_qrcode/' . $uid;
            $abs = $publicPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (!is_dir($abs)) {
                @mkdir($abs, 0777, true);
            }
            return [
                'key' => 'qrcode',
                'title' => '二维码目录',
                'rel' => $rel,
                'abs' => $abs,
            ];
        }
        if ($type === 'xls' || $type === 'data') {
            $rel = 'xls';
            $abs = $publicPath . DIRECTORY_SEPARATOR . 'xls';
            if (!is_dir($abs)) {
                @mkdir($abs, 0777, true);
            }
            return [
                'key' => 'xls',
                'title' => 'xls目录',
                'rel' => $rel,
                'abs' => $abs,
            ];
        }
        return null;
    }

    private function safeRelativePath($path)
    {
        $path = str_replace('\\', '/', (string)$path);
        $path = trim($path, '/');
        if ($path === '' || $path === '.') {
            return '';
        }
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            $parts[] = $part;
        }
        return implode('/', $parts);
    }

    private function joinPath($base, $rel)
    {
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR);
        $rel = $this->safeRelativePath($rel);
        if ($rel === '') {
            return $base;
        }
        return $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    }

    private function isPathInside($root, $target)
    {
        $rootReal = realpath($root);
        $targetReal = realpath($target);
        if ($rootReal === false || $targetReal === false) {
            return false;
        }
        $rootReal = rtrim(str_replace('\\', '/', $rootReal), '/');
        $targetReal = rtrim(str_replace('\\', '/', $targetReal), '/');
        return $targetReal === $rootReal || strpos($targetReal, $rootReal . '/') === 0;
    }

    private function dirSize($dir)
    {
        $size = 0;
        if (!is_dir($dir)) {
            return 0;
        }
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $size;
    }

    private function formatFileSize($bytes)
    {
        $bytes = (float)$bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }

    /*
     * 清空导出目录：type=qrcode|xls，path 为空清空根目录，否则清空当前子目录
    */
    public function clearExportDir($type = '', $path = '')
    {
        $resolved = $this->resolveExportRoot((string)$type);
        if (!$resolved) {
            return app('json')->fail('未知目录类型');
        }
        $safePath = $this->safeRelativePath($path);
        $dir = $this->joinPath($resolved['abs'], $safePath);
        if (!is_dir($dir) || !$this->isPathInside($resolved['abs'], $dir)) {
            return app('json')->fail('目录不存在');
        }
        $ok = delDirAndFile($dir);
        if ($ok) {
            @mkdir($dir, 0777, true);
        }
        return $ok ? app('json')->success('清空成功') : app('json')->fail('清空失败');
    }

    /*
     * 删除导出文件/文件夹：file 为相对根目录路径
    */
    public function deleteServerExportFile($file = '', $type = '')
    {
        $type = (string)$type;
        $safeRel = $this->safeRelativePath($file);
        if ($safeRel === '') {
            if ($type === 'qrcode') {
                return $this->clearExportDir('qrcode');
            }
            return app('json')->fail('非法文件名');
        }

        $resolved = $this->resolveExportRoot($type === 'data' ? 'xls' : ($type ?: 'xls'));
        if (!$resolved) {
            return app('json')->fail('未知目录类型');
        }
        $target = $this->joinPath($resolved['abs'], $safeRel);
        $absNorm = rtrim(str_replace('\\', '/', $resolved['abs']), '/');
        $targetNorm = rtrim(str_replace('\\', '/', $target), '/');
        if ($targetNorm !== $absNorm && strpos($targetNorm, $absNorm . '/') !== 0) {
            return app('json')->fail('非法路径');
        }
        if (is_dir($target)) {
            if ($targetNorm === $absNorm) {
                return app('json')->fail('不能删除根目录');
            }
            return delDirAndFile($target) ? app('json')->success('删除成功') : app('json')->fail('删除失败');
        }
        if (is_file($target)) {
            return unlink($target) ? app('json')->success('删除成功') : app('json')->fail('删除失败');
        }

        // 兼容旧 public/qrcode.zip
        $publicPath = get_public_path();
        $baseName = basename($safeRel);
        if ($baseName === 'qrcode.zip') {
            $okFile = true;
            $okDir = true;
            if (file_exists($publicPath . '/qrcode.zip')) {
                $okFile = unlink($publicPath . '/qrcode.zip');
            }
            if (file_exists($publicPath . '/qrcode')) {
                $okDir = delDirAndFile($publicPath . '/qrcode');
            }
            return ($okFile && $okDir) ? app('json')->success('删除成功') : app('json')->fail('删除失败');
        }
        return app('json')->fail('文件不存在');
    }

    /*
     * 商品码个人查询情况
     */
    public function getTotalCertifyResult($type, $page = 1, $size = 10)
    {
        if ($type != 'all' && $type != 'genuine' && $type != 'counterfeit' && $type != 'statistics') {
            return app('json')->fail();
        }
        $data = CodeService::getCodeResult($type, $page, $size);
        return app('json')->success($data);
    }

    /*
        * 商品码核销员核销情况
        */
    public function getUserWriteoffResult($type, $page = 1, $size = 10)
    {
        if ($type != 'all' && $type != 'statistics') {
            return app('json')->fail();
        }
        $data = CodeService::getWriteoffResult($type, $page, $size);
        return app('json')->success($data);
    }

    /*
        * 二维码生成进度
        */
    public function createQrcodeProgress()
    {

        $stop = request()->param('command');
        if ($stop) {
            $session['command'] = 'stop';
            $now_percent = setSessionProcessMsg('create_qrcode_progress', $session);

        } else {
            $now_percent = getSessionProcessMsg('create_qrcode_progress');
        }

        return app('json')->success($now_percent);
    }

    /*
        * 核销员二维码核销
        */
    public function writeoff($certify_code, $goods_id = '')
    {
        if ($certify_code) {
            $uid = TokenService::getCurrentUid();
            $res = CodeModel::writeoff($certify_code, $uid, TokenService::getCurrentAid());
            if ($res) {
                return app('json')->success();
            } else {
                return app('json')->fail();
            }
        }

    }

    /*
          * 发货
          */
    public function sendGoods($send_params)
    {
        if ($send_params) {
            $uid = TokenService::getCurrentUid();
            $res = CodeModel::sendGoods($send_params, $uid, TokenService::getCurrentAid());
            if ($res) {
                return app('json')->success();
            } else {

            }
        } else {
            return app('json')->fail();
        }

    }

    private function addMaYan($sourceImage, $margin = null, $size = null, $font = null,$logoPath=null)
    {

        $img_width = $img_height = 73;
        $mayan_center_height = 32;
        $im = @imagecreate($img_width, $img_height);
        $background_color = imagecolorallocate($im, 255, 255, 255);
        $text_color = imagecolorallocate($im, 0, 0, 0);
        imagestring($im, 3, 0, 0, getMayanString(10), $text_color);
        imagestringup($im, 3, 0, 0, getMayanString(10), $text_color);

        $sourceImagePath = $sourceImage;

        $logoImage = imagecreatefromstring(strval(file_get_contents($logoPath)));

        $sourceImage = imagecreatefromstring(strval(file_get_contents($sourceImage)));

        if (!is_resource($logoImage)) {
            throw new GenerateImageException('Unable to generate image: check your GD installation or logo path');
        }

        $logoSourceWidth = imagesx($logoImage);
        $logoSourceHeight = imagesy($logoImage);

        $logoWidth = $logoSourceWidth;

        $aspectRatio = $logoWidth / $logoSourceWidth;
        $logoHeight = intval($logoSourceHeight * $aspectRatio);

        $logoX = imagesx($sourceImage) / 2 - $logoWidth / 2;
        $logoY = imagesy($sourceImage) / 2 - $logoHeight / 2;

        imagecopyresampled($sourceImage, $logoImage, $margin, $margin, 0, 0, $logoWidth, $logoHeight, $logoSourceWidth, $logoSourceHeight);
        imagecopyresampled($sourceImage, $logoImage, $size - $logoWidth + $margin, $margin, 0, 0, $logoWidth, $logoHeight, $logoSourceWidth, $logoSourceHeight);
        imagecopyresampled($sourceImage, $logoImage, $margin, $size - $logoWidth + $margin, 0, 0, $logoWidth, $logoHeight, $logoSourceWidth, $logoSourceHeight);

        imagedestroy($logoImage);
        ob_start();
        //$qrCode->getImage()其实，就是生成二维码不保存到文件中，直接返回GD资源
        //imagepng展示出图片
        imagepng($sourceImage);
        $imageData = ob_get_contents();
        ob_end_clean();
        //$sourceImage = imagepng($sourceImage);
        file_put_contents($sourceImagePath, $imageData);
        imagedestroy($sourceImage);

        //return $sourceImage;
    }

    /*
    * 二维码生成
    */
    public function createQrcode($batch_id = '', $create_batch_id = '', $params = null, $selected_ids = [])
    {

        set_time_limit(0);
        $product_name = '商品';
        $filename = 'create_qrcode';
        define('PHP_LOCK_FILE', runtime_path() . "{$filename}.lock");
        define('LOCK_TIP', '已有任务运行中，请等待完成!');
        try {
            require __DIR__ . '/../../lock.php';

        } catch (Exception $e) {
            throw new TipException('已有任务运行中，请等待完成');
        }

        //保存码眼和中心logo
        if($params['logo']){
            SysConfig::where('key',  'logo')->update(['value' => $params['logo']]);
        }
        if($params['mayanImage']){
            SysConfig::where('key',  'mayanImage')->update(['value' => $params['logo']]);
        }
        $maYan = get_relative_path_from_url($params['mayanImage']);

        $session_value = ['command' => 'start', 'count' => 0, 'total' => 0, 'down_url' => '', 'msg' => '任务已开始', 'act' => 'start'];
        setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

        $root_path = get_public_path();

        $userId = TokenService::getCurrentAid();
        $userRoot = $root_path . '/code_qrcode/' . $userId;
        if (!is_dir($userRoot)) {
            if (!mkdir($userRoot, 0777, true) && !is_dir($userRoot)) {
                throw new TipException('创建二维码导出目录失败');
            }
        }
        // 仅清理未完成的临时图片目录，保留历史 zip 包供用户手动删除
        foreach (glob($userRoot . '/tmp_*') ?: [] as $tmpPath) {
            if (is_dir($tmpPath)) {
                try {
                    delDirAndFile($tmpPath);
                } catch (\Throwable $e) {
                }
            }
        }
        $exportKey = date('YmdHis') . '_' . substr((string)uniqid('', true), -6);
        $workDir = $userRoot . '/tmp_' . $exportKey;
        $zipFileName = 'qrcode_' . $exportKey . '.zip';
        $zipPath = $userRoot . '/' . $zipFileName;
        $zipUrl = get_public_url() . '/code_qrcode/' . $userId . '/' . $zipFileName;
        $task = null;

        //导出溯源码
        if(isset($params['type']) && $params['type']== 'traceCode'){

            switch ($params['scope']) {
                case '2':
                {
                    //批次
                    if($batch_id){
                        $data_model = CodeModel::where('batch_id', $batch_id);
                        $product = BatchModel::getProductByID($batch_id);
                        $task = BatchModel::where('id', $batch_id)->find()->toArray();
                        $product_name = process_file_name($product['goods_name']);
                        $dir = $workDir . '/batch_id_' . $batch_id;
                    }else{
                        throw new TipException('批次id不能为空！');
                    }
                    break;
                }
                case '3':
                {
                    //已选择
                    $data_model = CodeModel::whereIn('id', $selected_ids);
                    $product_name = '已选择';
                    $dir = $workDir . '/selected';
                    break;
                }
                case '5':
                {
                    //ID范围
                    if (!isset($params['start_id'], $params['end_id']) || $params['start_id'] > $params['end_id']) {
                        $session_value = ['count' => 0, 'total' => 0, 'down_url' => '', 'msg' => '起始id与终止id不合法', 'act' => 'error'];
                        setSessionProcessMsg('create_qrcode_progress', $session_value);
                        throw new TipException('起始id与终止id不合法！');
                    }
                    $data_model = CodeModel::where('id', '>=', $params['start_id'])->where('id', '<=', $params['end_id']);
                    if ($batch_id) {
                        $data_model = $data_model->where('batch_id', $batch_id);
                    }
                    $product_name = 'ID范围';
                    $dir = $workDir . '/id_range';
                    break;
                }
            }

        } else if ($create_batch_id) {
            switch ($params['scope']) {
                case '2':
                {//批次
                    $data_model = CodeModel::where('create_batch_id', $create_batch_id);
                    break;
                }
                case '5':
                {//ID范围
                    $data_model = CodeModel::where('create_batch_id', $create_batch_id);
                    $total = $params['end_id'] - $params['start_id'] + 1;
                    if ($total < 0) {
                        $session_value = ['count' => 0, 'total' => 0, 'down_url' => '', 'msg' => '起始id与终止id不合法', 'act' => 'error'];
                        setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
                        return app('json')->fail('起始id与终止id不合法！');
                    }
                    $data_model = $data_model->where([['id', '>=', $params['start_id']], ['id', '<=', $params['end_id']]]);
                    //}
                    break;
                }
            }
            $dir = $workDir;
        } else {//导出商品
            switch ($params['scope']) {
                case '2':
                {
                    //全部商品
                    $data_model = GoodsModel::where('goods_id', '>', '-1');
                    break;
                }
                case '3':
                {
                    //已选择

                    $data_model = GoodsModel::whereIn('goods_id', $selected_ids);
                    break;
                }
                case '5':
                {
                    //ID范围
                    $data_model = GoodsModel::where('goods_id', '>', '-1');

                    $total = $params['end_id'] - $params['start_id'] + 1;
                    if ($total < 0) {
                        $session_value = ['count' => 0, 'total' => 0, 'down_url' => '', 'msg' => '起始id与终止id不合法', 'act' => 'error'];
                        setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
                        return app('json')->fail('起始id与终止id不合法！');
                    }
                    $data_model = $data_model->where([['goods_id', '>=', $params['start_id']], ['goods_id', '<=', $params['end_id']]]);
                    break;
                }
            }
            $dir = $workDir;
        }


        if (!file_exists($dir)) {
            $res = mkdir($dir, 0777, true);
            if (!$res) {
                throw new TipException("创建目录($dir)出错");
            }
        }

        if ($params['code_type'] == 2) {
            //普通二维码
            $logo = $params['logo'];
            $logo = get_relative_path_from_url($logo);

            $qrCode = new \Endroid\QrCode\QrCode();

            $total = $data_model->count();
            $count = 0;
            foreach ($data_model->cursor() as $code) {
                $count++;
                if($params['type'] == 'traceCode'){//生成溯源码二维码
                    $file_name = $code['id'];
                    if ($params['short_url']) {//使用短网址
                        $url = $params['short_url'] . '/qr?c=' . $code['code'];

                    } else {
                        $url = get_public_index_url() . '/qr?c=' . $code['code'];
                    }
                } else {
                    $product_name = process_file_name($product_name);
                    $file_name = $product_name . '_' .$code['goods_id'] ;
                    if ($params['short_url']) {//使用短网址
                        $url = $params['short_url'] . '/qr?g=' . $code['goods_id'];

                    } else {
                        $url = get_public_index_url() . '/qr?g=' . $code['goods_id'];
                    }
                }

                //保存普通二维码
                $scene = 'type=code&' . $code['id'];
                try {

                    $qrCode->setText($url);
                    $qrCode->setSize(300);
                    $qrCode->setWriterByName('png');
                    $qrCode->setMargin(10);
                    $qrCode->setEncoding('UTF-8');
                    $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
                    $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
                    $qrCode->setRoundBlockSize(true);
                    $qrCode->setValidateResult(false);
                    $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
                    if ($logo) {
                        $qrCode->setLogoPath($logo);
                        $qrCode->setLogoSize(70, 70);
                    }
                    $font = $qrCode->getLabelFontPath();
                    $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
                    //$font_path = __DIR__.'/../assets/fonts/noto_sans.otf';
                    //$qrCode->setLabel('Scan the cod11e', 16);
                    $qrCode->setRoundBlockSize(false);
                    $margin = $qrCode->getMargin();
                    $size = $qrCode->getSize();
                    $qrCode->writeFile($dir . '/' . $file_name . '.png');
                    if ($params['use_mayan'] == 1) {
                        $this->addMaYan($dir . '/' . $file_name . '.png', $margin, $size, $font,$maYan);
                    }

                } catch (\Exception $e) {
                    $session_value = ['count' => $count, 'total' => $total, 'msg' => '保存普通二维码出错', 'act' => 'error', 'task' => $task];
                    setSessionProcessMsg('create_qrcode_progress', $session_value);
                    throw new TipException('保存普通二维码出错');
                }

                $command = getSessionProcessMsg('create_qrcode_progress');
                if (isset($command['command']) && $command['command'] == 'stop') {
                    die();
                }

                $session_value = ['count' => $count, 'id' => $code['id'], 'total' => $total, 'msg' => '正在生成普通二维码', 'act' => 'minicode', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

            }

            $session_value = ['count' => $count, 'total' => $total, 'msg' => '生成普通二维码完成', 'act' => 'minicode', 'task' => $task];
            setSessionProcessMsg('create_qrcode_progress', $session_value);

            if ($count > 0) {

                $session_value = ['count' => $count, 'total' => $total, 'msg' => '正在压缩', 'act' => 'zip', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

                try {
                    $res = self::zip($workDir, $zipPath);
                } catch (Throwable|Exception $e) {

                    $session_value = ['count' => $count, 'total' => $total, 'msg' => '压缩出错' . $e->getMessage(), 'act' => 'error', 'task' => $task];
                    setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
                    return app('json')->fail('失败', '压缩出错' . $e->getMessage());
                }

                // 压缩完成后清理临时图片，保留 zip 包
                try {
                    delDirAndFile($workDir);
                } catch (\Throwable $e) {
                }

                $url = $zipUrl;
                $data = [
                    'down_url' => $url,
                    'errcode' => 0
                ];

                $session_value = ['count' => $count, 'total' => $total, 'down_url' => $url, 'msg' => '完成', 'act' => 'over', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

                if ($res) {
                    return app('json')->success('成功', $data);
                }
            }else{
                $session_value = ['count' => $count, 'total' => $total, 'msg' => '导出的数量小于1', 'act' => 'error', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
                throw new TipException('导出的数量小于1');
            }

        } else {
            //小程序
            $app = MiniWechat::getMiniWechat();

            //删除qrcode目录

            $total = $data_model->count();
            $count = 0;

            set_time_limit(0);

            foreach ($data_model->cursor() as $code) {
                $count++;
                $id = $code['id'];

                if ($batch_id || $create_batch_id) {
                    $file_name = $code['id'];
                } else {
                    $file_name = $code['goods_id'];
                }

                //保存小程序码
                if ($batch_id) {
                    $scene = "c={$code['code']}";
                } else {
                    $scene = "g={$code['goods_id']}";
                }
                try {
                    $response = $app->app_code->getUnlimit($scene, [
                        'page' => 'pages/index/index',
                        'width' => 600,
                    ]);
                    if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                        $filename = $response->saveAs($dir, $file_name);
                    } else {
                        Log::error("保存小程序码出错");
                    }

                } catch (\Exception $e) {

                    $session_value = ['count' => $count, 'total' => $total, 'msg' => '保存小程序码出错', 'act' => 'minicode', 'task' => $task];
                    setSessionProcessMsg('create_qrcode_progress', $session_value);
                }
                $command = getSessionProcessMsg('create_qrcode_progress');
                if (isset($command['command']) && $command['command'] == 'stop') {
                    die();
                }

                $session_value = ['count' => $count, 'total' => $total, 'msg' => '正在生成小程序码', 'act' => 'minicode', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
            }


        };

        $session_value = ['count' => $count, 'total' => $total, 'msg' => '生成小程序码完成', 'act' => 'minicode', 'task' => $task];
        setSessionProcessMsg('create_qrcode_progress', $session_value);
        //压缩
        if ($count > 0) {

            $session_value = ['count' => $count, 'total' => $total, 'msg' => '正在压缩', 'act' => 'zip', 'task' => $task];
            setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

            try {
                $res = self::zip($workDir, $zipPath);
            } catch (Throwable|Exception $e) {

                $session_value = ['count' => $count, 'total' => $total, 'msg' => '压缩出错' . $e->getMessage(), 'act' => 'zip', 'task' => $task];
                setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁
                return app('json')->fail('失败', '压缩出错' . $e->getMessage());
            }

            try {
                delDirAndFile($workDir);
            } catch (\Throwable $e) {
            }

            $url = $zipUrl;
            $data = [
                'down_url' => $url,
                'errcode' => 0
            ];

            $session_value = ['count' => $count, 'total' => $total, 'down_url' => $url, 'msg' => '完成', 'act' => 'zip', 'task' => $task];
            setSessionProcessMsg('create_qrcode_progress', $session_value);  //释放session锁

            if ($res) {
                return app('json')->success('成功', $data);
            }
        }
        $session_value = ['count' => 0, 'total' => 0, 'down_url' => '', 'msg' => '完成', 'act' => 'over', 'task' => $task];
        setSessionProcessMsg('create_qrcode_progress', $session_value);

    }

    /*
    * 获取溯源码之间的个数
    */
    public function getTotalBetweenIds($start_id, $end_id)
    {
        $total = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id)->count();

        return app('json')->success($total);

    }

    /*
    * 溯源码数据删除进度提示
    */
    public function codeResetProgress()
    {
        $stop = request()->param('command');
        if ($stop) {
            $session['command'] = 'stop';
            $now_percent = setSessionProcessMsg('code_reset_progress', $session);

        } else {
            $now_percent = getSessionProcessMsg('code_reset_progress');
        }
        return app('json')->success($now_percent);
    }

    /*
    * 溯源码数据设置
    */
    public function codeReset($operation = null, $create_batch_id = null, $batch_id = null, $scope = null, $select_code_ids = null, $start_id = null, $end_id = null)
    {

        set_time_limit(0);

        $filename = 'create_qrcode';
        define('PHP_LOCK_FILE', runtime_path() . "{$filename}.lock");
        define('LOCK_TIP', '已有任务运行中，请等待完成!');
        try {
            require __DIR__ . '/../../lock.php';

        } catch (Exception $e) {
            throw new TipException('已有任务运行中，请等待完成');
        }

        $session = getSessionProcessMsg('code_reset_progress');
        $session['command'] = 'doing';
        $session['count'] = 0;
        $session['total'] = 0;
        $session['msg'] = '开始';
        setSessionProcessMsg('code_reset_progress', $session);

        $aid = TokenService::getCurrentAid();

        $total = 0;

        $obj = null;
        $batch = [];

        switch ($scope) {
            case '2':
            {
                //批次
                if ($batch_id) {
                    $total = Batch::where('id', $batch_id)->value('code_count');
                    $obj = CodeModel::where('batch_id', $batch_id);
                    $batch = Batch::find($batch_id);
                    if (!$batch) {
                        throw new TipException("批次不存在");
                    }
                    $session_value['task'] = ['batch' => $batch];
                    setSessionProcessMsg('code_reset_progress', $session);
                } else {
                    $session['msg'] = '批次不能为空';
                    $session['act'] = 'error';
                    setSessionProcessMsg('code_reset_progress', $session);
                    throw new TipException("批次不能为空");
                }
                if ($create_batch_id) {
                    if ($create_batch_id) throw new TipException("批次不能为空");
                    $total = Batch::where('create_batch_id', $create_batch_id)->value('code_count');
                    $obj = CodeModel::where('create_batch_id', $create_batch_id);
                }
                break;
            }
            case '3':
            {
                //选择
                $total = count($select_code_ids);
                $obj = CodeModel::whereIn('id', $select_code_ids);
                break;
            }
            case '5':
            { //ID范围
                $total = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id)->count();
                $obj = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id);
                break;
            }
        }
        if ($total <= 0) {
            $session = ['count' => 0, 'total' => 0, 'msg' => '总数为空', 'act' => 'error'];
            setSessionProcessMsg('code_reset_progress', $session);
            throw new TipException("总数为空");
        }

        $session = ['count' => 0, 'total' => $total, 'msg' => "开始", 'act' => 'starting'];
        setSessionProcessMsg('code_reset_progress', $session);
        $over = false;
        $line = 0;
        $data = $obj->field('id,code')->chunk(500, function ($codes) use ($aid, $operation, $total, $over, &$line) {

            $command = getSessionProcessMsg('code_reset_progress');
            if (isset($command['command']) && $command['command'] == 'stop') {
                $session_value = [
                    'count' => $line,
                    'total' => $total,
                    'msg' => '用户停止',
                    'act' => 'error',
                    'command' => 'stop',
                ];
                setSessionProcessMsg('code_reset_progress', $session_value);
                die();
            }
            $line += count($codes);
            $codes = $codes->column('code');

            if ($operation == 'ClearCertifyResult') {
                //消除查询记录
                $res2 = CodeResultModel::whereIn('code', $codes)->delete();
            }
            if ($operation == 'SetWriteoffStatus') {
                //设置核销
                foreach ($codes as $k => $v) {
                    $res2 = CodeModel::Writeoff($v, 0, $aid);
                }
            }
            if ($operation == 'ClearWriteoffStatus') {
                //清除核销
                $res2 = CodeWriteoffModel::whereIn('code', $codes)->delete();
            }

            $session_value = [
                'count' => $line,
                'total' => $total,
                'msg' => "已处理{$line}条",
                'act' => 'doing',
            ];
            setSessionProcessMsg('code_reset_progress', $session_value);  //释放session锁

        });

        $session_value = [
            'count' => $line,
            'total' => $total,
            'msg' => '完成',
            'act' => 'over',
        ];
        setSessionProcessMsg('code_reset_progress', $session_value);  //释放session锁


        $ret = [
            'count' => $line,
            'total' => $total,
            'errcode' => 0
        ];
        return app('json')->success($ret);

    }

    /*
    * 溯源码数据删除
    */
    public function codeDelete($create_batch_id, $batch_id, $scope, $select_code_ids, $start_id, $end_id)
    {
        $batch = [];
        if ($batch_id) {
            $batch = BatchModel::where('id', $batch_id)->find();
        }

        $session_value['command'] = 'doing';
        $session_value['task'] = ['batch' => $batch];
        setSessionProcessMsg('code_delete_progress', $session_value);

        static $line = 0;
        $total = 0;
        $obj = null;
        switch ($scope) {
            case '2':
            {
                //删除批次
                if ($batch_id) {
                    $total = CodeModel::where('batch_id', $batch_id)->count();
                    $obj = CodeModel::where('batch_id', $batch_id);
                } else {
                    $total = CodeModel::where('create_batch_id', $create_batch_id)->count();
                    $obj = CodeModel::where('create_batch_id', $create_batch_id);
                }
                break;
            }
            case '3':
            {
                //选择
                $total = count($select_code_ids);
                $obj = CodeModel::whereIn('id', $select_code_ids);
                break;
            }
            case '5':
            { //ID范围
                $total = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id)->count();
                $obj = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id);
                break;
            }
        }

        if (!$obj) {
            throw new TipException('请选择要删除的范围');
        }
        CodeModel::assertCanDeleteByQuery($obj);

        $session_value = ['count' => 0, 'total' => $total, 'msg' => "开始", 'act' => 'starting', 'task' => ['batch' => $batch]];
        setSessionProcessMsg('code_delete_progress', $session_value);
        $over = false;
        $data = $obj->field('id,code')->chunk(500, function ($codes) use ($batch, $total, $over, &$line) {

            $command = getSessionProcessMsg('code_delete_progress');
            if (isset($command['command']) && $command['command'] == 'stop') {
                $session_value['msg'] = "用户中断";
                $session_value['task'] = ['batch' => $batch];
                setSessionProcessMsg('code_delete_progress', $session_value);  //释放session锁
                die();
            }

            $line += count($codes);
            $codes->delete();

            $session_value['count'] = $line;
            $session_value['total'] = $total;
            $session_value['msg'] = "已处理{$line}条";
            $session_value['act'] = 'doing';
            $session_value['task'] = ['batch' => $batch];
            setSessionProcessMsg('code_delete_progress', $session_value);  //释放session锁

        });


        $session_value = [
            'total' => $total,
            'count' => $line,
            'msg' => '完成',
            'act' => 'over',
        ];
        $session_value['task'] = ['batch' => $batch];
        setSessionProcessMsg('code_delete_progress', $session_value);  //释放session锁

        $ret = [
            'count' => $line,
            'total' => $total,
            'errcode' => 0
        ];
        return app('json')->success($ret);

    }

    /*
    * 溯源码数据导出
    */
    public function dataExport($goods_id = '', $create_batch_id = '', $batch_id = '', $file_type = null, $scope = null, $select_code_ids = null, $start_id = null, $end_id = null)
    {

        $filename = 'create_qrcode';
        define('PHP_LOCK_FILE', runtime_path() . "{$filename}.lock");
        define('LOCK_TIP', '已有任务运行中，请等待完成!');
        try {
            require __DIR__ . '/../../lock.php';

        } catch (Exception $e) {
            throw new TipException('已有任务运行中，请等待完成');
        }
        set_time_limit(0);
        $session = getSessionProcessMsg('export_code_progress');
        $session['command'] = 'doing';
        $session['down_url'] = '';
        setSessionProcessMsg('export_code_progress', $session);

        $batch = null;;
        if ($batch_id) {
            $batch = BatchModel::find($batch_id);
            if ($batch) {
                $batch = $batch->toArray();
            }
        }
        //export_code导出溯源码（保留历史文件，不再清空整个 xls 目录）
        $root_path = get_public_path();
        $dir = $root_path . '/xls';

        if (!file_exists($dir)) {
            $res = mkdir($dir, 0777, true);
            if (!$res) {
                $session_value = ['act' => 'error', 'msg' => '创建目录出错', 'task' => ['batch' => $batch]];
                setSessionProcessMsg('export_code_progress', $session_value);
                return app('json')->fail('创建目录出错');
            }
        }
        $isCsv = ((string)$file_type === '2');
        $fileName = time() . ($isCsv ? '.csv' : '.xlsx');
        $path = $dir . '/' . $fileName; //产生随机文件名
        //表头
        $titleName = '';
        if ($goods_id) {
            $titleName = GoodsModel::where('goods_id', $goods_id)->value('goods_name');
        }
        if ($batch_id) {
            $titleName = BatchModel::where('id', $batch_id)->value('name');
        }
        if ($create_batch_id) {
            $titleName = CreateBatch::where('id', $create_batch_id)->value('name');
        }

        $line = 0;

        $total = 0;

        $obj = null;
        try {
            switch ((string)$scope) {
                case '2':
                    if ($batch_id) {
                        $total = CodeModel::where('batch_id', $batch_id)->count();
                        $obj = CodeModel::where('batch_id', $batch_id);
                    } elseif ($create_batch_id) {
                        $total = CodeModel::where('create_batch_id', $create_batch_id)->count();
                        $obj = CodeModel::where('create_batch_id', $create_batch_id);
                    } else {
                        throw new TipException('请选择批次');
                    }
                    break;
                case '3':
                    if (empty($select_code_ids) || !is_array($select_code_ids)) {
                        throw new TipException('请选择溯源码');
                    }
                    $total = count($select_code_ids);
                    $obj = CodeModel::whereIn('id', $select_code_ids);
                    break;
                case '5':
                    $start_id = intval($start_id);
                    $end_id = intval($end_id);
                    if ($start_id <= 0 || $end_id <= 0) {
                        throw new TipException('请输入起始ID和终止ID');
                    }
                    if ($start_id > $end_id) {
                        throw new TipException('起始id和终止id不合法！');
                    }
                    $total = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id)->count();
                    if ($total <= 0) {
                        throw new TipException('该ID范围内没有溯源码');
                    }
                    $obj = CodeModel::where('id', '>=', $start_id)->where('id', '<=', $end_id);
                    break;
                default:
                    throw new TipException('请选择导出数据范围');
            }

            if (!$obj) {
                throw new TipException('没有可导出的数据');
            }

            $data = $obj->field('id,code')->chunk(500, function ($codes) use ($batch, $path, $total, &$line, $goods_id, $isCsv) {

                $command = getSessionProcessMsg('export_code_progress');
                if (isset($command['command']) && $command['command'] == 'stop') {
                    die();
                }

                $line += count($codes);
                $over = ($total == $line);
                $codes = $codes->toArray();
                if ($isCsv) {
                    exportCsv($codes, $path, ['savePath' => $path, 'over' => $over], $goods_id);
                } else {
                    exportExcel($codes, $path, ['savePath' => $path, 'over' => $over], $goods_id);
                }
                $session_value = ['count' => $line, 'total' => $total, 'msg' => "已处理{$line}条", 'act' => 'doing', 'task' => ['batch' => $batch]];
                setSessionProcessMsg('export_code_progress', $session_value);  //释放session锁

            });

            // 确保 CSV 句柄关闭；Excel 若末块未落盘则强制保存
            if ($isCsv) {
                exportCsv([], $path, ['savePath' => $path, 'over' => true], $goods_id);
            } else {
                exportExcel([], $path, ['savePath' => $path, 'flushOnly' => true], $goods_id);
            }

            $session_value = [
                'count' => $line,
                'msg' => '完成',
                'act' => 'over',
                'total' => $total,
                'task' => ['batch' => $batch],
                'down_url' => get_public_url() . '/xls/' . $fileName
            ];
            setSessionProcessMsg('export_code_progress', $session_value);  //释放session锁
        } catch (TipException $e) {
            $session_value = ['act' => 'error', 'msg' => $e->getMessage(), 'task' => ['batch' => $batch]];
            setSessionProcessMsg('export_code_progress', $session_value);
            throw $e;
        } catch (\Throwable $e) {
            $msg = $e->getMessage() ?: '导出失败';
            $session_value = ['act' => 'error', 'msg' => $msg, 'task' => ['batch' => $batch]];
            setSessionProcessMsg('export_code_progress', $session_value);
            throw new TipException($msg);
        }

        return app('json')->success($session_value);
    }

    /*
      * 二维码管理中获取小程序和普通二维码
      */
    public function getQrcodeList($goods_id = '', $code = '')
    {
        //普通二维码
        $qrCode = new \Endroid\QrCode\QrCode();

//        $product = GoodsModel::where('goods_id', $goods_id)->find();
//        if (!$product) {
//            return app('json')->fail('未找到产品');
//        }
        if ($code) {
            $url = get_public_index_url() . '/qr?c=' . $code;
        } else {
            $url = get_public_index_url() . '/qr?g=' . $goods_id;
        }
        $qrCode->setText($url);
        $qrCode->setSize(300);
        $qrCode->setWriterByName('png');
        $qrCode->setMargin(10);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLogoSize(150, 200);
        $qrCode->setRoundBlockSize(true);
        $qrCode->setValidateResult(false);
        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
        $qr_images = $qrCode->writeString();


        try {
            //小程序
            $app = MiniWechat::getMiniWechat();
            //小程序码
            if ($code) {
                $scene = "c={$code}";
            } else {
                $scene = "g={$goods_id}";
            }

            $response = $app->app_code->getUnlimit($scene, [
                'page' => 'pages/index/index',
                'width' => 600,
            ]);
            $contents = $response->getBody()->getContents();

        } catch (Throwable|Exception $e) {
            $contents = '';
        }

        $res['qr_miniapp'] = base64_encode($contents);
        $res['qr_h5'] = base64_encode($qr_images);
        $res['qr_h5_url'] = $url;

        return app('json')->success($res);

    }

    /*
         * 获取小程序二维码 小程序分享时
         */
    public function getMiniQrcode($goods_id)
    {
        try {
            //小程序
            $app = MiniWechat::getMiniWechat();
            //保存小程序码
            $scene = 'g=' . $goods_id;

            $response = $app->app_code->getUnlimit($scene, [
                'page' => 'pages/index/index',
                'width' => 600,
            ]);
            $contents = $response->getBody()->getContents();

        } catch (Throwable|Exception $e) {
            $contents = '';
        }

        $res['mini_qrcode'] = base64_encode($contents);

        return app('json')->success($res);

    }

    /*
      * 核销数据列表
      */
    public function getAllWriteoff($page = 1, $size = 10, $uid = '', $aid = '', $search_code = '')
    {
        $res = CodeWriteoffModel::getAllList($page, $size, $uid, $aid, $search_code);

        return app('json')->success($res);

    }

    /*
      * 查询数据列表
      */
    public function getAllCodeCertifyResult($page = 1, $size = 10, $uid = '', $aid = '', $search = '')
    {
        $page = (int) (input('page', $page) ?: 1);
        $size = (int) (input('size', $size) ?: 10);
        $uid = input('uid', $uid);
        $aid = input('aid', $aid);
        $search = input('search', $search);
        if (is_string($search) && $search !== '') {
            $decoded = json_decode($search, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $search = $decoded;
            }
        }
        $res = CodeResultModel::getAllList($page, $size, $uid, $aid, $search);

        return app('json')->success($res);

    }

    /*
      * 删除核销数据
      */
    public function deleteWriteoff($id)
    {
        $res = CodeWriteoffModel::where('id', $id)->delete();

        return app('json')->success($res);

    }

    /**
     * @desc  生成zip压缩文件的函数
     *
     * @param $dir             string 需要压缩的文件夹名
     * @param $filename     string 压缩后的zip文件名  包括zip后缀
     * @param $missfile      array   不需要的文件
     * @param $fromString  array   自定义压缩文件
     */
    public function zip($dir, $filename, $missfile = array(), $addfromString = array())
    {

        if (!file_exists($dir) || !is_dir($dir)) {
            throw new Exception(' can not exists dir ' . $dir);
        }
        $ext_test = explode('.', $filename);
        if (strtolower(end($ext_test)) != 'zip') {
            throw new Exception('only Support zip files');
        }
        $dir = str_replace('\\', '/', $dir);
        $filename = str_replace('\\', '/', $filename);
        //$filename = iconv('utf-8', 'UTF-8', $filename);

        if (file_exists($filename)) {
            unlink($filename);
        }

        $files = array();
        self::getfiles($dir, $files);
//        $files    = self::arrayIconv($files);
        if (empty($files)) {
            throw new Exception(' qrcode目录为空');
        }
        $zip = new \ZipArchive();
        $res = $zip->open($filename, \ZipArchive::CREATE);
        if ($res === TRUE) {
            foreach ($files as $v) {
                if (!in_array(str_replace($dir . '/', '', $v), $missfile)) {
                    $zip->addFile($v, str_replace($dir . '/', './', $v));
                }
            }
            if (!empty($addfromString)) {
                foreach ($addfromString as $v) {
                    $zip->addFromString($v[0], $v[1]);
                }
            }
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    public function getfiles($dir, &$files = array())
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return mkdir($dir);
        }
        if (substr($dir, -1) == '/') {
            $dir = substr($dir, 0, strlen($dir) - 1);
        }
        $_files = scandir($dir);
        foreach ($_files as $v) {
            if ($v != '.' && $v != '..') {
                if (is_dir($dir . '/' . $v)) {
                    self::getfiles($dir . '/' . $v, $files);
                } else {
                    $files[] = $dir . '/' . $v;
                }
            }
        }
        return $files;
    }

}
