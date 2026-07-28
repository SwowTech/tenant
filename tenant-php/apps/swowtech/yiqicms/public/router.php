<?php



declare(strict_types=1);



/**

 * PHP built-in server router for swowtech/yiqicms.

 * Upstream paths are relative to app root: /, /admin/, /install/, ...

 * CMS 大量使用相对 include（如 ../inc/），进入子目录脚本前必须 chdir。

 *

 * 重要：禁止在函数内 require 入口脚本。

 * zzzcms 依赖顶层作用域写入 $GLOBALS（如 $language），函数内 require 会导致前台 404。

 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$uri = rawurldecode($uri);



if ($uri === '/health') {

    header('Content-Type: text/plain; charset=utf-8');

    echo 'ok';

    return true;

}



if (! getenv('APP_SITE_PATH')) {

    putenv('APP_SITE_PATH=/swowtech/yiqicms');

    $_ENV['APP_SITE_PATH'] = '/swowtech/yiqicms';

}



$appRoot = dirname(__DIR__);

$file = $appRoot . str_replace('/', DIRECTORY_SEPARATOR, $uri);



$scriptFile = null;



// 真实静态文件直接交给内置服务器

if ($uri !== '/' && is_file($file) && ! str_ends_with(strtolower($uri), '.php')) {

    return false;

}



// 显式 .php

if ($uri !== '/' && is_file($file) && str_ends_with(strtolower($uri), '.php')) {

    $scriptFile = $file;

}



// 目录下的 index.php（如 /install/ → install/index.php）

if ($scriptFile === null) {

    $dirCandidate = $file;

    if (str_ends_with($uri, '/')) {

        $dirCandidate = rtrim($file, '/\\');

    }

    if (is_dir($dirCandidate)) {

        $index = $dirCandidate . DIRECTORY_SEPARATOR . 'index.php';

        if (is_file($index)) {

            $scriptFile = $index;

        }

    }

}



// 前台入口

if ($scriptFile === null) {

    $scriptFile = $appRoot . DIRECTORY_SEPARATOR . 'index.php';

}



chdir(dirname($scriptFile));

require $scriptFile;



return true;


