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

use App\Service\IService;
use Hyperf\Crontab\Strategy\Executor;
use Hyperf\Database\Model\Collection;
use Plugin\MineAdmin\Crontab\Model\Crontab;
use Plugin\MineAdmin\Crontab\Model\CrontabExecuteLog;

/**
 * @extends IService<Crontab>
 */
class Service extends IService
{
    public function __construct(
        protected readonly Repository $repository,
        protected readonly Executor $executor
    ) {}

    /**
     * @throws \Throwable
     */
    public function execute(mixed $id): void
    {
        /**
         * @var Collection|Crontab[] $list
         */
        $list = $this->repository->getQuery()->whereKey($id)->get();
        foreach ($list as $cronEntity) {
            $cron = new \Mine\Crontab\Crontab($cronEntity->id);
            $this->executor->execute($cron);
        }
    }

    public function logList(array $params = [], int $page = 1, int $pageSize = 10): array
    {
        $query = CrontabExecuteLog::query();

        if (isset($params['crontab_id'])) {
            $query->where('crontab_id', $params['crontab_id']);
        }
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (isset($params['name'])) {
            $query->where('name', 'like', "%{$params['name']}%");
        }
        if (isset($params['created_at'])) {
            $query->whereBetween('created_at', $params['created_at']);
        }

        $total = $query->count();
        $list = $query->orderByDesc('created_at')
            ->forPage($page, $pageSize)
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
