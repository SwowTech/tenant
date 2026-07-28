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
use Hyperf\Crontab\Strategy\WorkerStrategy;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Mine\Upload\Factory;
use Mine\Upload\UploadInterface;

return [
    UploadInterface::class => Factory::class,
    CheckTokenInterface::class => PassportService::class,
    // Hyperf built-in (plugin Strategy needs plugin autoload; bare server deploy often fails without it)
    StrategyInterface::class => WorkerStrategy::class,
];
