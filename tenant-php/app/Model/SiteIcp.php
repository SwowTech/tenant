<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model as MineModel;

/**
 * @property int $id
 * @property string $domain
 * @property string $icp
 * @property string $police
 * @property string $license_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class SiteIcp extends MineModel
{
    protected ?string $table = 'site_icp';

    protected array $fillable = [
        'domain',
        'icp',
        'police',
        'license_url',
    ];

    protected array $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
