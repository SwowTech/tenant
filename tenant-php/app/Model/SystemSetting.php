<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model as MineModel;

/**
 * @property int $id
 * @property string $key
 * @property array|null $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class SystemSetting extends MineModel
{
    protected ?string $table = 'system_setting';

    protected array $fillable = [
        'key',
        'value',
    ];

    protected array $casts = [
        'id' => 'integer',
        'value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
