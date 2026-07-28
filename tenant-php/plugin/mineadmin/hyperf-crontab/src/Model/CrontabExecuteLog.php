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

namespace Plugin\MineAdmin\Crontab\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $crontab_id 任务ID
 * @property string $name 任务名称
 * @property int $status 执行状态 0:失败 1:成功
 * @property string $target 执行目标
 * @property string $exception_info 异常信息
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Crontab $crontab
 */
class CrontabExecuteLog extends Model
{
    protected ?string $table = 'crontab_execute_log';

    protected array $fillable = [
        'id', 'crontab_id', 'name',
        'status', 'target', 'exception_info',
    ];

    protected array $casts = [
        'crontab_id' => 'int',
        'status' => 'int',
        'exception_info' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function crontab(): BelongsTo
    {
        return $this->belongsTo(Crontab::class);
    }
}
