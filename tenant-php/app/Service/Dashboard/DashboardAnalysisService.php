<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Model\OpsTenant;
use App\Service\Founder\FounderTenantService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Throwable;

final class DashboardAnalysisService
{
    public function __construct(
        private readonly DashboardScopeResolver $scopeResolver,
        private readonly DashboardAggregate $aggregate,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    public function get(): array
    {
        $resolved = $this->scopeResolver->resolve();
        $scope = $resolved['scope'];
        $payload = $scope === DashboardScopeResolver::SCOPE_PLATFORM
            ? $this->platform()
            : $this->siteOrTenant();

        return array_merge($resolved, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function siteOrTenant(): array
    {
        $today = Carbon::today();
        $last7From = $today->copy()->subDays(6);
        $prev7From = $today->copy()->subDays(13);
        $prev7To = $today->copy()->subDays(7);
        $last14From = $today->copy()->subDays(13);

        $users = $this->aggregate->tableCount('user');
        $logins = $this->aggregate->countBetween(
            'user_login_log',
            'login_time',
            $last7From,
            $today,
            static fn ($q) => $q->where('status', 1)
        );
        $prevLogins = $this->aggregate->countBetween(
            'user_login_log',
            'login_time',
            $prev7From,
            $prev7To,
            static fn ($q) => $q->where('status', 1)
        );
        $ops = $this->aggregate->countBetween('user_operation_log', 'created_at', $last7From, $today);
        $prevOps = $this->aggregate->countBetween('user_operation_log', 'created_at', $prev7From, $prev7To);
        $attachments = $this->aggregate->tableCount('attachment');
        $prevAttachments = $this->aggregate->countBetween(
            'attachment',
            'created_at',
            $prev7From,
            $prev7To
        );
        $newAttachments = $this->aggregate->countBetween(
            'attachment',
            'created_at',
            $last7From,
            $today
        );

        $dates = $this->aggregate->dateRange($last14From, $today);
        $loginMap = $this->aggregate->countByDate(
            'user_login_log',
            'login_time',
            $last14From,
            $today,
            static fn ($q) => $q->where('status', 1)
        );
        $opsMap = $this->aggregate->countByDate('user_operation_log', 'created_at', $last14From, $today);

        $failLogins = $this->aggregate->countBetween(
            'user_login_log',
            'login_time',
            $last7From,
            $today,
            static fn ($q) => $q->where('status', 2)
        );

        return [
            'kpis' => [
                [
                    'key' => 'users',
                    'title' => 'users',
                    'count' => $users,
                    'growth' => 0.0,
                ],
                [
                    'key' => 'logins',
                    'title' => 'logins',
                    'count' => $logins,
                    'growth' => $this->aggregate->growthPercent($logins, $prevLogins),
                ],
                [
                    'key' => 'ops',
                    'title' => 'ops',
                    'count' => $ops,
                    'growth' => $this->aggregate->growthPercent($ops, $prevOps),
                ],
                [
                    'key' => 'attachments',
                    'title' => 'attachments',
                    'count' => $attachments,
                    'growth' => $this->aggregate->growthPercent($newAttachments, $prevAttachments),
                ],
            ],
            'trend' => [
                'dates' => $dates,
                'series' => [
                    ['key' => 'login_ok', 'name' => 'login_ok', 'data' => $this->aggregate->fillDaily($loginMap, $dates)],
                    ['key' => 'ops', 'name' => 'ops', 'data' => $this->aggregate->fillDaily($opsMap, $dates)],
                ],
            ],
            'ranking' => $this->aggregate->topGrouped(
                'user_operation_log',
                'username',
                $last7From,
                $today,
                'created_at',
                10
            ),
            'pie' => [
                ['key' => 'success', 'name' => 'success', 'value' => $logins],
                ['key' => 'fail', 'name' => 'fail', 'value' => $failLogins],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function platform(): array
    {
        $today = Carbon::today();
        $last14From = $today->copy()->subDays(13);

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($today, $last14From) {
            $tenants = OpsTenant::query()->get();
            $total = $tenants->count();
            $active = $tenants->where('status', FounderTenantService::STATUS_ACTIVE)->count();
            $pending = $tenants->whereIn('status', [
                FounderTenantService::STATUS_PROVISIONING,
                FounderTenantService::STATUS_PROVISION_FAILED,
            ])->count();
            $disabled = $tenants->where('status', FounderTenantService::STATUS_DISABLED)->count();

            $appInstances = 0;
            foreach ($tenants as $tenant) {
                $prefix = (string) ($tenant->table_prefix ?: TenantInfo::tablePrefixForId((int) $tenant->id));
                $this->dynamicTablePrefix->apply($prefix);
                try {
                    if (\Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
                        $appInstances += (int) Db::table('tenant_installed_app')->where('status', 1)->count();
                    }
                } catch (Throwable) {
                }
            }
            $this->dynamicTablePrefix->apply('');

            $dates = $this->aggregate->dateRange($last14From, $today);
            $createMap = [];
            try {
                $rows = OpsTenant::query()
                    ->where('created_at', '>=', $last14From->copy()->startOfDay())
                    ->where('created_at', '<=', $today->copy()->endOfDay())
                    ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                    ->groupBy('d')
                    ->get();
                foreach ($rows as $row) {
                    $createMap[(string) $row->d] = (int) $row->c;
                }
            } catch (Throwable) {
            }

            $recent = OpsTenant::query()
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'name', 'domain', 'status', 'created_at'])
                ->map(static fn ($t) => [
                    'name' => (string) $t->name,
                    'value' => (int) $t->id,
                    'domain' => (string) $t->domain,
                    'status' => (int) $t->status,
                    'created_at' => (string) $t->created_at,
                ])
                ->all();

            return [
                'kpis' => [
                    ['key' => 'tenants', 'title' => 'tenants', 'count' => $total, 'growth' => 0.0],
                    ['key' => 'active', 'title' => 'active', 'count' => $active, 'growth' => 0.0],
                    ['key' => 'pending', 'title' => 'pending', 'count' => $pending, 'growth' => 0.0],
                    ['key' => 'apps', 'title' => 'apps', 'count' => $appInstances, 'growth' => 0.0],
                ],
                'trend' => [
                    'dates' => $dates,
                    'series' => [
                        ['key' => 'new_tenants', 'name' => 'new_tenants', 'data' => $this->aggregate->fillDaily($createMap, $dates)],
                    ],
                ],
                'ranking' => array_map(static fn ($r) => [
                    'name' => $r['name'] . ' (' . $r['domain'] . ')',
                    'value' => $r['status'],
                ], $recent),
                'pie' => [
                    ['key' => 'active', 'name' => 'active', 'value' => $active],
                    ['key' => 'disabled', 'name' => 'disabled', 'value' => $disabled],
                    ['key' => 'pending', 'name' => 'pending', 'value' => $pending],
                ],
                'recent_tenants' => $recent,
            ];
        });
    }
}
