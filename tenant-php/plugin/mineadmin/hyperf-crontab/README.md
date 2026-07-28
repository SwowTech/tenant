# 可视化定时器任务

## 安装

## 1. 进入到后端根目录，第一步下载应用

```shell
php bin/hyperf.php mine-extension:download mineadmin/hyperf-crontab
```

## 2. 安装 `mineadmin/crontab` 

```shell
composer require mineadmin/crontab
```

## 3. 发布 `hyperf/crontab` 配置文件

```shell
php bin/hyperf vendor:publish hyperf/crontab
```

## 4. 配置 `config/autoload/` 目录下的 `listeners.php` `aspects.php` `dependencies.php`

1. dependencies.php 加入 `StrategyInterface::class => WorkerStrategy::class`

```php
<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
use App\Service\PassportService;
use Hyperf\Crontab\Strategy\StrategyInterface;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Mine\Upload\Factory;
use Mine\Upload\UploadInterface;
use Plugin\MineAdmin\Crontab\Strategy\WorkerStrategy;

return [
    UploadInterface::class => Factory::class,
    CheckTokenInterface::class => PassportService::class,
    StrategyInterface::class => WorkerStrategy::class
];

```

2. aspects.php 加入 `\Mine\Crontab\Aspect\CrontabExecutorAspect::class`

```php
<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
return [
    \Mine\Crontab\Aspect\CrontabExecutorAspect::class
];

```

3. listeners.php 加入 `\Mine\Crontab\Listener\CrontabProcessStarredListener::class`

```php
<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
use Hyperf\ExceptionHandler\Listener\ErrorExceptionHandler;
use Mine\Core\Subscriber\BootApplicationSubscriber;
use Mine\Core\Subscriber\DbQueryExecutedSubscriber;
use Mine\Core\Subscriber\FailToHandleSubscriber;
use Mine\Core\Subscriber\QueueHandleSubscriber;
use Mine\Core\Subscriber\ResumeExitCoordinatorSubscriber;
use Mine\Core\Subscriber\Upload\UploadSubscriber;
use Mine\Support\Listener\RegisterBlueprintListener;

return [
    ErrorExceptionHandler::class,
    // 默认文件上传
    UploadSubscriber::class,
    // 处理程序启动
    BootApplicationSubscriber::class,
    // 处理 sql 执行
    DbQueryExecutedSubscriber::class,
    // 处理命令异常
    FailToHandleSubscriber::class,
    // 处理 worker 退出
    ResumeExitCoordinatorSubscriber::class,
    // 处理队列
    QueueHandleSubscriber::class,
    // 注册新的 Blueprint 宏
    RegisterBlueprintListener::class,
    \Mine\Crontab\Listener\CrontabProcessStarredListener::class,
];

```


#  安装应用

```shell
php bin/hyperf.php mine-extension:install mineadmin/hyperf-crontab
```