<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model as MineModel;

/**
 * @property int $id
 * @property string $name
 * @property string $app_id
 * @property string $app_secret
 * @property string $token
 * @property string $encoding_aes_key
 * @property int $level
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class WechatAccount extends MineModel
{
    protected ?string $table = 'wechat_account';

    protected array $fillable = [
        'name',
        'app_id',
        'app_secret',
        'token',
        'encoding_aes_key',
        'level',
        'status',
    ];

    protected array $casts = [
        'id' => 'integer',
        'level' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
