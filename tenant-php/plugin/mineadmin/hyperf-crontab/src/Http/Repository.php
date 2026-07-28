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

namespace Plugin\MineAdmin\Crontab\Http;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\MineAdmin\Crontab\Model\Crontab;

/**
 * @extends IRepository<Crontab>
 */
class Repository extends IRepository
{
    public function __construct(
        protected readonly Crontab $model
    ) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query->with(['execute_logs'])
            ->when(isset($params['name']), static function (Builder $query) use ($params) {
                return $query->where('name', 'like', "%{$params['name']}%");
            })
            ->when(isset($params['status']), static function (Builder $query) use ($params) {
                return $query->where('status', $params['status']);
            })
            ->when(isset($params['type']), static function (Builder $query) use ($params) {
                return $query->where('type', $params['type']);
            })
            ->when(isset($params['is_on_one_server']), static function (Builder $query) use ($params) {
                return $query->where('is_on_one_server', $params['is_on_one_server']);
            })
            ->when(isset($params['is_singleton']), static function (Builder $query) use ($params) {
                return $query->where('is_singleton', $params['is_singleton']);
            })
            ->when(isset($params['created_at']), static function (Builder $query) use ($params) {
                return $query->whereBetween('created_at', $params['created_at']);
            })
            ->when(isset($params['updated_at']), static function (Builder $query) use ($params) {
                return $query->whereBetween('updated_at', $params['updated_at']);
            })
            ->when(isset($params['memo']), static function (Builder $query) use ($params) {
                return $query->where('memo', 'like', "%{$params['memo']}%");
            })
            ->orderByDesc('created_at');
    }
}
