<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
namespace think;

require __DIR__ . '/./vendor/autoload.php';

if ((session_status() != PHP_SESSION_ACTIVE)) {
    session_start();
}

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
