<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $domain
 * @property int $tenant_id
 * @property string $identifier
 * @property string $scheme
 * @property int $is_primary
 */
final class OpsAppDomain extends Model
{
    protected ?string $table = 'ops_app_domain';

    protected array $fillable = [
        'domain',
        'tenant_id',
        'identifier',
        'scheme',
        'is_primary',
    ];

    protected array $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'is_primary' => 'integer',
    ];
}
