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
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;
use Plugin\MineAdmin\Crontab\Enums\CrontabType;

/**
 * @property int $id
 * @property string $name 名称
 * @property string $memo 备注
 * @property bool $status 状态
 * @property bool $is_on_one_server 是否只在一台服务器上运行
 * @property bool $is_singleton 是否单例
 * @property CrontabType $type 类型
 * @property string $rule 规则
 * @property string $value 值
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property CrontabExecuteLog[] $executeLogs
 */
class Crontab extends Model
{
    protected ?string $table = \Mine\Crontab\Crontab::TABLE;

    protected array $fillable = [
        'id', 'name', 'memo',
        'status', 'is_on_one_server',
        'is_singleton', 'type', 'rule',
        'value',
    ];

    protected array $casts = [
        'status' => 'bool',
        'is_on_one_server' => 'bool',
        'is_singleton' => 'bool',
        'type' => CrontabType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $attributes = [
        'memo' => '',
    ];

    public function execute_logs(): HasMany
    {
        return $this->hasMany(CrontabExecuteLog::class)->orderByDesc('created_at');
    }
}
