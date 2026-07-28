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

namespace Plugin\MineAdmin\Crontab\Enums;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\ConstantsTrait;

#[Constants]
enum CrontabType: string
{
    use ConstantsTrait;

    #[Message('访问 url')]
    case Url = 'url';

    #[Message('执行某个类的 execute 方法')]
    case Classes = 'class';

    #[Message('执行某块代码')]
    case Eval = 'eval';

    #[Message('执行某个回调函数')]
    case Callback = 'callback';

    #[Message('执行某个命令')]
    case Command = 'command';
}
