<?php
PHP_VERSION < '5.3' AND die( '很抱歉，程序需要PHP版本要求5.3以上，请更换PHP版本。' );
$starttime = microtime( 1 );
$time = time();
session_start();
// 头部，判断是否运行在命令行下
define( 'IN_CMD', !empty( $_SERVER[ 'SHELL' ] ) || empty( $_SERVER[ 'REMOTE_ADDR' ] ) );
if ( IN_CMD ) {
	!isset( $_SERVER[ 'REMOTE_ADDR' ] )AND $_SERVER[ 'REMOTE_ADDR' ] = '';
	!isset( $_SERVER[ 'REQUEST_URI' ] )AND $_SERVER[ 'REQUEST_URI' ] = '';
	!isset( $_SERVER[ 'REQUEST_METHOD' ] )AND $_SERVER[ 'REQUEST_METHOD' ] = 'GET';
} else {
	header( 'Content-Type:text/html; charset=utf-8' );
	header( 'X-UA-Compatible:IE=edge,chrome=1' );
}

// 设置中国时区
date_default_timezone_set( 'Asia/Shanghai' );
// 超级全局变量
!empty( $_SERVER[ 'HTTP_X_REWRITE_URL' ] )AND $_SERVER[ 'REQUEST_URI' ] = $_SERVER[ 'HTTP_X_REWRITE_URL' ];
!isset( $_SERVER[ 'REQUEST_URI' ] )AND $_SERVER[ 'REQUEST_URI' ] = '';
$_SERVER[ 'REQUEST_URI' ] = str_replace( '/index.php?', '/', $_SERVER[ 'REQUEST_URI' ] ); // 兼容 iis6

// 定义站点物理路径与虚拟目录（应用包装：优先 APP_SITE_PATH）
$appRoot = str_replace('\\', '/', dirname(__DIR__));
$envSitePath = getenv('APP_SITE_PATH');
if (is_string($envSitePath) && $envSitePath !== '') {
    $sitePath = '/' . trim(str_replace('\\', '/', $envSitePath), '/') . '/';
    if ($sitePath === '//') {
        $sitePath = '/';
    }
    define('SITE_PATH', $sitePath);
    define('SITE_DIR', rtrim($appRoot, '/') . '/');
    define('DOC_DIR', rtrim($appRoot, '/'));
} else {
    define('DOC_DIR', str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')));
    $script_path = explode('/', $_SERVER['SCRIPT_NAME']);
    $file_path = $appRoot;

    if (count($script_path) > 2) {
        if (!!$path_pos = strrpos($file_path, '/' . $script_path[1])) {
            define('SITE_PATH', substr($file_path, $path_pos) . '/');
        } else {
            define('SITE_PATH', '/');
        }
    } else {
        define('SITE_PATH', '/');
    }
    define('SITE_DIR', DOC_DIR . SITE_PATH);
}
define( 'CONF_DIR', SITE_DIR . 'config/' );
// 正式部署：宿主未注入 DB_* 时，从 user-php/.env 兜底
$hostEnv = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'host_env.php';
if (is_file($hostEnv)) {
	require_once $hostEnv;
	if (function_exists('mine_apps_load_host_env')) {
		mine_apps_load_host_env();
	}
}
include SITE_DIR . 'config/zzz_config.php';
// 宿主网关启动时可注入 DB_*，覆盖本地安装配置中的空密码等
if (is_array($conf['db'] ?? null)) {
	$host = getenv('DB_HOST');
	if (is_string($host) && $host !== '') {
		$conf['db']['host'] = $host;
	}
	$port = getenv('DB_PORT');
	if (is_string($port) && $port !== '') {
		$conf['db']['port'] = $port;
	}
	$user = getenv('DB_USERNAME');
	if (is_string($user) && $user !== '') {
		$conf['db']['user'] = $user;
	}
	$password = getenv('DB_PASSWORD');
	if ($password !== false) {
		$conf['db']['password'] = (string) $password;
	}
	$dbName = getenv('YIQICMS_DB_NAME');
	if ($dbName === false || $dbName === '') {
		$dbName = getenv('APP_DB_NAME');
	}
	if ($dbName === false || $dbName === '') {
		$dbName = getenv('DB_DATABASE');
	}
	if (is_string($dbName) && $dbName !== '') {
		$conf['db']['name'] = $dbName;
	}
}
include 'zzz_version.php';
include 'zzz_main.php';
include 'zzz_file.php';
include 'zzz_array.php';
include 'zzz_cache.php';
include 'zzz_db.php';
define( 'PLUG_PATH', SITE_PATH . 'plugins/' );
define( 'PLUG_DIR', SITE_DIR . 'plugins/' );
define( 'RUN_DIR', SITE_DIR . 'runtime/' );
define( 'UPLOAD_DIR', SITE_DIR . $conf[ 'uploadpath' ] );
define( 'ADMIN_DIR', SITE_DIR . $conf[ 'adminpath' ] );
define( 'ADMIN_PATH', SITE_PATH . $conf[ 'adminpath' ] );

define( 'ISSESSION', 1 ); //1是session存储，0是cookie存储
// 1: 开发模式 0: 关闭
!defined( 'DEBUG' )AND define( 'DEBUG',$conf["bugmark"]);
function_exists( 'ini_set' )AND ini_set( 'display_errors', DEBUG ? '1' : '0' );
error_reporting( DEBUG ? E_ALL : 0 );
$ip = ip();
$longip = ip2long( $ip );
$longip < 0 AND $longip = sprintf( "%u", $longip ); // fix 32 位 OS 下溢出的问题
$useragent = _SERVER( 'HTTP_USER_AGENT' );
// 语言包变量
!isset( $lang )AND $lang = array();
// 全局的错误，非多线程下很方便。
$errno = 0;
$errstr = '';

// IP 地址
!isset( $_SERVER[ 'REMOTE_ADDR' ] )AND $_SERVER[ 'REMOTE_ADDR' ] = 'localhost';
!isset( $_SERVER[ 'SERVER_ADDR' ] )AND $_SERVER[ 'SERVER_ADDR' ] = 'localhost';

$method = $_SERVER[ 'REQUEST_METHOD' ];

define( 'DB_TYPE', $conf[ 'db' ][ 'type' ] );
define( 'DB_PRE',  $conf[ 'db' ][ 'tablepre' ] );

// 保存到超级全局变量，防止冲突被覆盖。
$_SERVER[ 'starttime' ] = $starttime;
$_SERVER[ 'time' ] = $time;
$_SERVER[ 'ip' ] = $ip;
$_SERVER[ 'longip' ] = $longip;
$_SERVER[ 'useragent' ] = $useragent;
$_SERVER[ 'conf' ] = $conf;
$_SERVER[ 'prefix' ] = $conf[ 'prefix' ];
$_SERVER[ 'lang' ] = $lang;
$_SERVER[ 'errno' ] = $errno;
$_SERVER[ 'errstr' ] = $errstr;
$_SERVER[ 'method' ] = $method;
$db = db_new( $conf[ 'db' ] );
$_SERVER[ 'db' ] = $db;
if ( $conf[ 'isinstall' ] == 1 ) {
	define( 'INSTALL', '1' );
	db_connect();
} else {
	define( 'INSTALL', '0' );
}
defined( 'LANGUAGE' ) ? defined( 'LANGUAGE' ) : define( 'LANGUAGE', 'ch' );
$language = db_load_one('language',["l_alias"=>LANGUAGE]);
// 显式写入 $GLOBALS：入口若在函数内 require，顶层赋值不会进入全局
$GLOBALS['language'] = $language;
?>