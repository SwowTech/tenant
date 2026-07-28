<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model as MineModel;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $domain
 * @property string $custom_domain
 * @property string $table_prefix
 * @property int $status
 * @property string $contact_phone
 * @property string $contact_email
 * @property string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OpsTenant extends MineModel
{
    protected ?string $table = 'ops_tenant';

    protected array $fillable = [
        'code',
        'name',
        'domain',
        'custom_domain',
        'table_prefix',
        'status',
        'contact_phone',
        'contact_email',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
