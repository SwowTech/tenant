#!/usr/bin/env php
<?php

declare(strict_types=1);

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;
use Mine\AppStore\Plugin;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('START_TIME') && define('START_TIME', time());
! defined('HF_VERSION') && define('HF_VERSION', '3.1');

require BASE_PATH . '/vendor/autoload.php';
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

(function () {
    Plugin::init();
    ClassLoader::init(handler: new ProcScanHandler());
    $container = require BASE_PATH . '/config/container.php';
    $application = $container->get(ApplicationInterface::class);
    $application->run();
})();
