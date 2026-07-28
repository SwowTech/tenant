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

namespace Plugin\MineAdmin\Crontab\Strategy;

use Hyperf\Crontab\Crontab;
use Hyperf\Crontab\Strategy\WorkerStrategy as BaseWorkerStrategy;

class WorkerStrategy extends BaseWorkerStrategy
{
    public function dispatch(Crontab $crontab): void
    {
        if ($crontab->isEnable()) {
            parent::dispatch($crontab);
        }
    }
}
