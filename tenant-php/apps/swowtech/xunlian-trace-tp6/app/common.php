<?php
// 应用公共文件


use app\model\SysConfig;
use app\Request;
use app\services\TokenService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use think\Cache;
use think\facade\Log;

const CODE_STATUS_NOMOAL = 0;
const CODE_STATUS_CERTIFIED = 1;
const CODE_STATUS_BANDED = 2;
const CODE_STATUS_NOTEXIST = 3;

const VENDOR_URL = 'http://auth.flyinginternet.cn';

const ORIGANAL_VERSION = 'xlsy-qyb_v1.0.0';
const ORIGANAL_DESC = '讯联溯源企业版 v1.0.0';

/**
 * 通过url获取本服务器的真实地址
 */
function get_real_path_from_server_url($url)
{
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($host && strpos($url, $host) !== false) {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $real = realpath('.' . $path);
            return $real ?: false;
        }
    }
    return false;
}

/*处理文件名

*/
function process_file_name($name)
{
    $name = str_replace(['/', '\\', ':', '*', '"', '<', '>', '|', '?'], '_', $name);
    return $name;
}

/**
 * 通过url获取本服务器的真实地址
 */
function get_relative_path_from_url($url)
{
    if (!$url) {
        return false;
    }
//    $public_url = get_public_url_preg();
    $pattern = '/http[s]?:\/\/(.*?)\/(.*)/';
    preg_match($pattern, $url, $match);
    if ($match && $match[2]) {
        $path = $_SERVER['DOCUMENT_ROOT']. '/' . $match[2];
        $path = str_replace('\\', '/', $path);
        if(is_file($path)){
            return $path;
        }else {
            return false;
        }
    }
    return false;
}

//获取围绕码眼的字符串
function getMayanString($count)
{
    $num = '';
    for ($i = 0; $i < $count; $i++) {

        $num .= rand(0, 9);
    }

    return $num;
}

function getTodayStart()
{
    return date('Y-m-d', time()) . ' 0:0:0';
}

function getTodayEnd()
{
    return date('Y-m-d', time()) . ' 23:59:59';
}

function getMonthStart()
{
    return date('Y-m', time()) . '-01 0:0:0';
}

function getMonthEnd()
{
    $month_start = getMonthStart();
    return date("Y-m-d", strtotime("$month_start +1 month -1 day")) . ' 23:59:59';
}

function number2chinese($num)
{
    if (is_int($num) && $num < 100) {
        $char = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $unit = ['', '十', '百', '千', '万'];
        $return = '';
        if ($num < 10) {
            $return = $char[$num];
        } elseif ($num % 10 == 0) {
            $firstNum = substr($num, 0, 1);
            if ($num != 10) $return .= $char[$firstNum];
            $return .= $unit[strlen($num) - 1];
        } elseif ($num < 20) {
            $return = $unit[substr($num, 0, -1)] . $char[substr($num, -1)];
        } else {
            $numData = str_split($num);
            $numLength = count($numData) - 1;
            foreach ($numData as $k => $v) {
                if ($k == $numLength) continue;
                $return .= $char[$v];
                if ($v != 0) $return .= $unit[$numLength - $k];
            }
            $return .= $char[substr($num, -1)];
        }
        return $return;
    }
}

function curl_get($url, &$httpCode = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $file_contents = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}

function curl_post($url, array $params = array())
{
    $data_string = json_encode($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

function curl_post_xml($url, $xml)
{
    $data_string = $xml;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: text/xml'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

//判断系统是否完成安装
function vae_is_installed()
{
    static $vaeIsInstalled;
    if (empty($vaeIsInstalled)) {
        $vaeIsInstalled = file_exists(VAE_ROOT . 'data/install.lock');
    }
    return $vaeIsInstalled;
}

//生成订单编号
function makeOrderNum()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
    $orderSn =
        $yCode[intval(date('Y')) - 2018] . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
            '%02d', rand(0, 99));
    return $orderSn;
}

//生成拼图订单编号
function makePtOrderNum()
{
    $orderSn =
        "P" . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
            '%02d', rand(0, 99));
    return $orderSn;
}

/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0, &$res = null)
{
    if ($res === null) {
        $res = [];
    }
    foreach ($param as $key => $vo) {
        if ($pid == $vo['pid']) {
            $pre_space = '';
            for ($i = 0; $i < $vo['level']; $i++) {
                $pre_space = $pre_space . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            $vo['select_name'] = $pre_space . $vo['name'];//选择框选项的名称
            $res[] = $vo;
            subTree($param, $vo['id'], $res);
        }
    }
    return $res;
}

// 根据父类id找所有子类
function _getSon($data, $p_id = 0, $level = 0, $isClear = true)
{
    //声明一个静态数组存储结果
    static $res = array();
    //刚进入函数要清除上次调用此函数后留下的静态变量的值，进入深一层循环时则不要清除
    if ($isClear == true) $res = array();
    foreach ($data as $v) {
        if ($v['parent_id'] == $p_id) {
            $v['level'] = $level;
            $res[] = $v;
            _getSon($data, $v['id'], $level + 1, $isClear = false);
        }
    }
    return $res;
}


/**
 * 管理员密码加密方式
 * @param $password  密码
 * @param $password_code 密码额外加密字符
 * @return string
 */
function password($password)
{
    $password_code = config('setting.psw_code');
    return md5(md5($password) . md5($password_code));
}

/**
 * 获取当前页面完整URL地址
 */

function get_full_url()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

/**
 * 获取public完整URL地址
 */

function get_public_url()
{
    $publicBase = (string) ($_SERVER['HTTP_X_APP_PUBLIC_BASE'] ?? '');
    if ($publicBase !== '') {
        return rtrim($publicBase, '/');
    }
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $sitePath = '';
    if (($_SERVER['HTTP_X_APP_ROOT'] ?? '') !== '1') {
        $sitePath = (string) (getenv('APP_SITE_PATH') ?: '');
    }
    return $sys_protocal . $host . $sitePath;
}

/**
 * 获取public完整URL地址
 */

function get_public_url_preg()
{
    $publicBase = (string) ($_SERVER['HTTP_X_APP_PUBLIC_BASE'] ?? '');
    if ($publicBase !== '') {
        return str_replace(['/', ':'], ['\\/', '\\:'], rtrim($publicBase, '/'));
    }
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https:\\/\\/' : 'http:\\/\\/';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $sitePath = '';
    if (($_SERVER['HTTP_X_APP_ROOT'] ?? '') !== '1') {
        $sitePath = (string) (getenv('APP_SITE_PATH') ?: '');
        $sitePath = str_replace('/', '\\/', $sitePath);
    }
    return $sys_protocal . $host . $sitePath;
}

//请求应用类型，公众号或普通网页,
function system_app_type()
{
    $mpId = SysConfig::where('key', 'gzh_appid')->value('value');
    if ($mpId) {
        $res = 'wechat';
    } else {
        $res = 'html';
    }
    return $res;
}

/**
 * 获取public完整URL地址
 */

function get_public_index_url()
{
    $publicBase = (string) ($_SERVER['HTTP_X_APP_PUBLIC_BASE'] ?? '');
    if ($publicBase !== '') {
        return rtrim($publicBase, '/');
    }
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $sitePath = '';
    if (($_SERVER['HTTP_X_APP_ROOT'] ?? '') !== '1') {
        $sitePath = (string) (getenv('APP_SITE_PATH') ?: '');
    }
    return $sys_protocal . $host . $sitePath;
}

class Num2Cny
{

    static $basical = array(0 => '零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');

    static $advanced = array(1 => '拾', '佰', '仟');

    public static function ParseNumber($number)
    {

        $number = trim($number);

        if (!is_numeric($number) || intval($number) > 999999999999) return 'error';

        if ($number == 0) return '零';

        if (strpos($number, '.')) {

            $number = round($number, 2);

            $data = explode('.', $number);

            $data[0] = self::int($data[0]);

            $data[1] = self::dec($data[1]);

            return $data[0] . $data[1];

        } else {

            return self::int($number) . '整';

        }

    }

    public static function int($number)
    {

        $arr = array_reverse(str_split($number));

        $data = '';

        $zero = false;

        $zero_num = 0;

        foreach ($arr as $k => $v) {

            $_chinese = '';

            $zero = ($v == 0) ? true : false;

            $x = $k % 4;

            if ($x && $zero && $zero_num > 1) continue;

            switch ($x) {

                case 0:

                    if ($zero) {

                        $zero_num = 0;

                    } else {

                        $_chinese = self::$basical[$v];

                        $zero_num = 1;

                    }

                    if ($k == 8) {

                        $_chinese .= '亿';

                    } elseif ($k == 4) {

                        $_chinese .= '万';

                    }

                    break;

                default:

                    if ($zero) {

                        if ($zero_num == 1) {

                            $_chinese = self::$basical[$v];

                            $zero_num++;

                        }

                    } else {

                        $_chinese = self::$basical[$v];

                        $_chinese .= self::$advanced[$x];

                    }

            }

            $data = $_chinese . $data;

        }

        return $data . '元';

    }

    public static function dec($number)
    {

        if (strlen($number) < 2) $number .= '0';

        $arr = array_reverse(str_split($number));

        $data = '';

        $zero_num = false;

        foreach ($arr as $k => $v) {

            $zero = ($v == 0) ? true : false;

            $_chinese = '';

            if ($k == 0) {

                if (!$zero) {

                    $_chinese = self::$basical[$v];

                    $_chinese .= '分';

                    $zero_num = true;

                }

            } else {

                if ($zero) {

                    if ($zero_num) {

                        $_chinese = self::$basical[$v];

                    }

                } else {

                    $_chinese = self::$basical[$v];

                    $_chinese .= '角';

                }

            }

            $data = $_chinese . $data;

        }
        return $data;
    }
}

/**
 * 获取当前页面完整URL地址
 */

function get_public_path()
{

    $root_path = app()->getRootPath();
    $root_path .= 'public';
    return $root_path;
}

/**
 * 上传到宿主统一存储（本地或创始人配置的 OSS）.
 * 成功返回数组含 url/relative_url；失败返回 null（调用方回退本地）.
 *
 * @return null|array{url:string,relative_url:string,storage_mode:string,object_name?:string,storage_path?:string}
 */
/**
 * 跨盘安全移动（Windows 上 rename 从 C: 临时目录到 D: 会失败）.
 */
function move_upload_file_safe(string $from, string $to): bool
{
    if (! is_file($from)) {
        return false;
    }
    $dir = dirname($to);
    if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
        return false;
    }
    if (@rename($from, $to)) {
        return true;
    }
    if (@copy($from, $to) && is_file($to)) {
        @unlink($from);

        return true;
    }

    return false;
}

function host_storage_upload(string $localFile, string $originName, string $category = 'misc'): ?array
{
    if (! is_file($localFile)) {
        return null;
    }
    $base = rtrim((string) (getenv('MINE_HOST_INTERNAL_URL') ?: ''), '/');
    if ($base === '') {
        $base = 'http://127.0.0.1:9501';
    }
    $secret = (string) (getenv('APP_GATEWAY_SECRET') ?: '');
    if ($secret === '') {
        $secret = (string) ($_SERVER['HTTP_X_APP_GATEWAY_SECRET'] ?? '');
    }
    $tenantId = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
    $identifier = (string) ($_SERVER['HTTP_X_APP_IDENTIFIER'] ?? (getenv('APP_IDENTIFIER') ?: 'swowtech/xunlian-trace-tp6'));

    $boundary = '----MineUp' . uniqid('', true);
    $filename = basename($originName !== '' ? $originName : $localFile);
    $fileContents = (string) file_get_contents($localFile);
    $body = '';
    $fields = [
        'tenant_id' => (string) $tenantId,
        'identifier' => $identifier,
        'category' => $category,
    ];
    foreach ($fields as $k => $v) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"{$k}\"\r\n\r\n{$v}\r\n";
    }
    $body .= "--{$boundary}\r\n";
    $body .= 'Content-Disposition: form-data; name="file"; filename="' . str_replace('"', '', $filename) . "\"\r\n";
    $body .= "Content-Type: application/octet-stream\r\n\r\n";
    $body .= $fileContents . "\r\n";
    $body .= "--{$boundary}--\r\n";

    $headers = [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
        'X-App-Gateway-Secret: ' . $secret,
        'X-Tenant-Id: ' . $tenantId,
        'X-App-Identifier: ' . $identifier,
    ];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 60,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($base . '/internal/storage/upload', false, $ctx);
    if ($raw === false || $raw === '') {
        return null;
    }
    $json = json_decode($raw, true);
    if (! is_array($json) || (int) ($json['code'] ?? 0) !== 200 || ! is_array($json['data'] ?? null)) {
        return null;
    }
    /** @var array{url?:string,relative_url?:string,storage_mode?:string} $data */
    $data = $json['data'];
    if (empty($data['url'])) {
        return null;
    }

    return [
        'url' => (string) $data['url'],
        'relative_url' => (string) ($data['relative_url'] ?? ''),
        'storage_mode' => (string) ($data['storage_mode'] ?? 'local'),
        'object_name' => (string) ($data['object_name'] ?? ''),
        'storage_path' => (string) ($data['storage_path'] ?? ''),
    ];
}

/**
 * 租户+应用隔离上传相对目录（相对 public/uploads）
 * 例：tenant/31/app/swowtech_xunlian-trace-tp6/product
 * 后续 OSS 对象 key 沿用同一结构。
 */
function tenant_upload_rel(string $category = ''): string
{
    $tenantId = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
    $app = (string) ($_SERVER['HTTP_X_APP_IDENTIFIER'] ?? 'swowtech/xunlian-trace-tp6');
    $app = str_replace('\\', '/', $app);
    $appKey = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $app) ?: 'app';
    $parts = ['tenant', (string) max(0, $tenantId), 'app', $appKey];
    $category = trim(str_replace(['\\', '..'], ['/', ''], $category), '/');
    if ($category !== '') {
        $parts[] = $category;
    }

    return implode('/', $parts);
}

/** 磁盘绝对目录：public/uploads/tenant/.../category */
function tenant_upload_dir(string $category = ''): string
{
    $dir = get_public_path() . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, tenant_upload_rel($category));
    if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
        throw new \RuntimeException('创建上传目录失败: ' . $dir);
    }

    return $dir;
}

/** URL 路径：/uploads/tenant/.../category/filename */
function tenant_upload_url_path(string $category, string $filename): string
{
    return '/uploads/' . tenant_upload_rel($category) . '/' . ltrim($filename, '/');
}

//循环删除目录和文件函数（保留顶层目录本身）
function delDirAndFile($dirName, bool $removeSelf = false): bool
{
    $dirName = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $dirName), DIRECTORY_SEPARATOR);
    if ($dirName === '' || ! is_dir($dirName)) {
        return true;
    }
    $items = @scandir($dirName);
    if ($items === false) {
        return false;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dirName . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            if (! delDirAndFile($path, true)) {
                return false;
            }
            continue;
        }
        if (is_file($path) || is_link($path)) {
            if (! @unlink($path)) {
                return false;
            }
        }
    }
    if ($removeSelf && ! @rmdir($dirName)) {
        return false;
    }

    return true;
}

function deleteFiles($dir = 'temp/', $fileType = "/^.*\.(png|svg|gif|jpeg|jpg|eps)$/i")
{
    if (file_exists($dir) && $handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (file_exists($dir . '/' . $file)) {
                if (preg_match($fileType, $file)) {
                    unlink($dir . '/' . $file);
                }
            }
        }
        closedir($handle);
    }
}

function buildImageFullPath($url)
{
    if (strstr($url, 'http://') || strstr($url, 'https://')) {
        return $url;
    }
    return get_public_url() . $url;
}

//设置处理进度消息
function getSessionProcessMsg($sessionName)
{
    $uid = TokenService::getCurrentAid();
    $session_value = cache($uid.'-'.$sessionName);
    return $session_value;
}

//设置处理进度消息
function setSessionProcessMsg($sessionName, $session_value)
{
    $uid = TokenService::getCurrentAid();
    cache($uid.'-'.$sessionName, $session_value);
    return $session_value;
}

//处理文件名中的非法字符
function formatFileName($path_name)
{
    $path_name = str_replace(['/', '\\', ':', '*', '"', '<', '>', '|', '?'], '_', $path_name);
    return $path_name;
}

/**
 * Excel导出，TODO 可继续优化
 *
 * @param array $datas 导出数据，格式['A1' => 'XXXX公司报表', 'B1' => '序号']
 * @param string $fileName 导出文件名称
 * @param array $options 操作选项，例如：
 *                           bool   print       设置打印格式
 *                           string freezePane  锁定行数，例如表头为第一行，则锁定表头输入A2
 *                           array  setARGB     设置背景色，例如['A1', 'C1']
 *                           array  setWidth    设置宽度，例如['A' => 30, 'C' => 20]
 *                           bool   setBorder   设置单元格边框
 *                           array  mergeCells  设置合并单元格，例如['A1:J1' => 'A1:J1']
 *                           array  formula     设置公式，例如['F2' => '=IF(D2>0,E42/D2,0)']
 *                           array  format      设置格式，整列设置，例如['A' => 'General']
 *                           array  alignCenter 设置居中样式，例如['A1', 'A2']
 *                           array  bold        设置加粗样式，例如['A1', 'A2']
 *                           string savePath    保存路径，设置后则文件保存到服务器，不通过浏览器下载
 */
function exportExcel(array $datas, string $fileName = '', array $options = [], $goods_id = null): bool
{
    try {
        /** @var Spreadsheet $objSpreadsheet */

        static $objSpreadsheet = null;
        static $line = 0;

        // 仅将已累积数据写入磁盘并释放
        if (!empty($options['flushOnly'])) {
            if ($objSpreadsheet && !empty($options['savePath'])) {
                $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
                $objWriter->save($options['savePath']);
                $objSpreadsheet->disconnectWorksheets();
                unset($objSpreadsheet);
                $objSpreadsheet = null;
                $line = 0;
            }
            return true;
        }

        if (empty($datas)) {
            return false;
        }

        if (!$objSpreadsheet) {
            $objSpreadsheet = app(Spreadsheet::class);
            $line = 0;
        }
        /* 设置默认文字居左，上下居中 */
        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $objSpreadsheet->getDefaultStyle()->applyFromArray($styleArray);
        /* 设置Excel Sheet */
        $activeSheet = $objSpreadsheet->setActiveSheetIndex(0);

        /* 打印设置 */
        if (isset($options['print']) && $options['print']) {
            /* 设置打印为A4效果 */
            $activeSheet->getPageSetup()->setPaperSize(PageSetup:: PAPERSIZE_A4);
            /* 设置打印时边距 */
            $pValue = 1 / 2.54;
            $activeSheet->getPageMargins()->setTop($pValue / 2);
            $activeSheet->getPageMargins()->setBottom($pValue * 2);
            $activeSheet->getPageMargins()->setLeft($pValue / 2);
            $activeSheet->getPageMargins()->setRight($pValue / 2);
        }

        /* 行数据处理 */
        foreach ($datas as $sKey => $sItem) {
            /* 默认文本格式 */
            $pDataType = DataType::TYPE_STRING;

            /* 设置单元格格式 */
            if (isset($options['format']) && !empty($options['format'])) {
                $colRow = Coordinate::coordinateFromString($sKey);

                /* 存在该列格式并且有特殊格式 */
                if (isset($options['format'][$colRow[0]]) &&
                    NumberFormat::FORMAT_GENERAL != $options['format'][$colRow[0]]) {
                    $activeSheet->getStyle($sKey)->getNumberFormat()
                        ->setFormatCode($options['format'][$colRow[0]]);

                    if (false !== strpos($options['format'][$colRow[0]], '0.00') &&
                        is_numeric(str_replace(['￥', ','], '', $sItem))) {
                        /* 数字格式转换为数字单元格 */
                        $pDataType = DataType::TYPE_NUMERIC;
                        $sItem = str_replace(['￥', ','], '', $sItem);
                    }
                } elseif (is_int($sItem)) {
                    $pDataType = DataType::TYPE_NUMERIC;
                }
            }
            $activeSheet->setCellValueExplicit('a' . ++$line, $sItem['id'], $pDataType);
            $activeSheet->setCellValueExplicit('b' . $line, $sItem['code'], $pDataType);
            $activeSheet->setCellValueExplicit('c' . $line, getQrUrl($goods_id, $sItem['code']), $pDataType);
            Log::debug($line);
            if($line>174500){
    Log::error($line);
}

            /* 存在:形式的合并行列，列入A1:B2，则对应合并 */
            if (false !== strstr($sKey, ":")) {
                $options['mergeCells'][$sKey] = $sKey;
            }
        }

        unset($datas);

        /* 设置锁定行 */
        if (isset($options['freezePane']) && !empty($options['freezePane'])) {
            $activeSheet->freezePane($options['freezePane']);
            unset($options['freezePane']);
        }

        /* 设置宽度 */
        if (isset($options['setWidth']) && !empty($options['setWidth'])) {
            foreach ($options['setWidth'] as $swKey => $swItem) {
                $activeSheet->getColumnDimension($swKey)->setWidth($swItem);
            }

            unset($options['setWidth']);
        }

        /* 设置背景色 */
        if (isset($options['setARGB']) && !empty($options['setARGB'])) {
            foreach ($options['setARGB'] as $sItem) {
                $activeSheet->getStyle($sItem)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }

            unset($options['setARGB']);
        }

        /* 设置公式 */
        if (isset($options['formula']) && !empty($options['formula'])) {
            foreach ($options['formula'] as $fKey => $fItem) {
                $activeSheet->setCellValue($fKey, $fItem);
            }

            unset($options['formula']);
        }

        /* 合并行列处理 */
        if (isset($options['mergeCells']) && !empty($options['mergeCells'])) {
            $activeSheet->setMergeCells($options['mergeCells']);
            unset($options['mergeCells']);
        }

        /* 设置居中 */
        if (isset($options['alignCenter']) && !empty($options['alignCenter'])) {
            $styleArray = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            foreach ($options['alignCenter'] as $acItem) {
                $activeSheet->getStyle($acItem)->applyFromArray($styleArray);
            }

            unset($options['alignCenter']);
        }

        /* 设置加粗 */
        if (isset($options['bold']) && !empty($options['bold'])) {
            foreach ($options['bold'] as $bItem) {
                $activeSheet->getStyle($bItem)->getFont()->setBold(true);
            }

            unset($options['bold']);
        }

        /* 设置单元格边框，整个表格设置即可，必须在数据填充后才可以获取到最大行列 */
        if (isset($options['setBorder']) && $options['setBorder']) {
            $border = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN, // 设置border样式
                        'color' => ['argb' => 'FF000000'], // 设置border颜色
                    ],
                ],
            ];
            $setBorder = 'A1:' . $activeSheet->getHighestColumn() . $activeSheet->getHighestRow();
            $activeSheet->getStyle($setBorder)->applyFromArray($border);
            unset($options['setBorder']);
        }

        $fileName = !empty($fileName) ? $fileName : (date('YmdHis') . '.xlsx');

        if (!isset($options['savePath'])) {
            /* 直接导出Excel，无需保存到本地，输出07Excel文件 */
            ob_clean();
            ob_start();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header(
                "Content-Disposition:attachment;filename=" . iconv("utf-8", "GB2312//TRANSLIT", $fileName)
            );
            header('Cache-Control: max-age=0');//禁止缓存
            $savePath = 'php://output';
            ob_end_flush();
            unset($objSpreadsheet);
        } else {
            $savePath = $options['savePath'];
        }


        if ($options['over']) {
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            $objWriter->save($savePath);
            /* 释放内存 */
            $objSpreadsheet->disconnectWorksheets();
            unset($objSpreadsheet);
            $objSpreadsheet = null;
            $line = 0;
        }

        return true;
    } catch (Exception $e) {

        return false;
    }
}

/**
 * 分块追加写入 CSV（UTF-8 BOM，便于 Excel 打开）
 */
function exportCsv(array $datas, string $fileName = '', array $options = [], $goods_id = null): bool
{
    static $fp = null;

    try {
        $savePath = $options['savePath'] ?? $fileName;
        if (empty($savePath)) {
            return false;
        }

        if ($fp === null) {
            $isNew = !file_exists($savePath) || filesize($savePath) === 0;
            $fp = fopen($savePath, 'a');
            if ($fp === false) {
                return false;
            }
            if ($isNew) {
                // UTF-8 BOM，避免 Excel 打开中文乱码
                fwrite($fp, "\xEF\xBB\xBF");
                fputcsv($fp, ['ID', '溯源码', '链接']);
            }
        }

        foreach ($datas as $item) {
            fputcsv($fp, [
                $item['id'] ?? '',
                $item['code'] ?? '',
                getQrUrl($goods_id, $item['code'] ?? ''),
            ]);
        }

        if (!empty($options['over'])) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            $fp = null;
        }

        return true;
    } catch (\Throwable $e) {
        if (is_resource($fp)) {
            fclose($fp);
        }
        $fp = null;
        return false;
    }
}

//获取二维码扫码地址
function getQrUrl($goods_id, $code)
{
    $url = get_public_url() . '/qr?';
    if ($goods_id) {
        $url .= 'g=' . $goods_id;
    }
    if ($code) {
        $url .= 'c=' . $code;
    }
    return $url;
}
//
//function getCaptcha(Request $request)
//{
//    ob_clean();
//    $rep = captcha();
//    $key = app('session')->get('captcha.key');
//    $uni = $request->get('key');
//    if ($uni)
//        Cache::set('sms.key.cap.' . $uni, $key, 300);
//
//    return $rep;
//}