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

final class DashboardReportService
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
        $last30From = $today->copy()->subDays(29);
        $last7From = $today->copy()->subDays(6);

        $dates = $this->aggregate->dateRange($last30From, $today);
        $loginMap = $this->aggregate->countByDate(
            'user_login_log',
            'login_time',
            $last30From,
            $today,
            static fn ($q) => $q->where('status', 1)
        );
        $opsMap = $this->aggregate->countByDate('user_operation_log', 'created_at', $last30From, $today);
        $userMap = $this->aggregate->countByDate('user', 'created_at', $last30From, $today);

        $weekDates = $this->aggregate->dateRange($last7From, $today);
        $weekLoginMap = $this->aggregate->countByDate(
            'user_login_log',
            'login_time',
            $last7From,
            $today,
            static fn ($q) => $q->where('status', 1)
        );
        $weekOpsMap = $this->aggregate->countByDate('user_operation_log', 'created_at', $last7From, $today);
        $weekAttachMap = $this->aggregate->countByDate('attachment', 'created_at', $last7From, $today);

        $retention = $this->retentionUsers($last7From, $today);

        return [
            'overview' => [
                'dates' => $dates,
                'series' => [
                    ['key' => 'login_ok', 'name' => 'login_ok', 'data' => $this->aggregate->fillDaily($loginMap, $dates)],
                    ['key' => 'ops', 'name' => 'ops', 'data' => $this->aggregate->fillDaily($opsMap, $dates)],
                    ['key' => 'new_users', 'name' => 'new_users', 'data' => $this->aggregate->fillDaily($userMap, $dates)],
                ],
                'summary' => [
                    ['key' => 'logins_30', 'title' => 'logins_30', 'value' => array_sum($loginMap), 'icon' => 'heroicons:user-16-solid', 'color' => '#165DFF'],
                    ['key' => 'ops_30', 'title' => 'ops_30', 'value' => array_sum($opsMap), 'icon' => 'heroicons:hand-thumb-up', 'color' => '#F77234'],
                    ['key' => 'new_users_30', 'title' => 'new_users_30', 'value' => array_sum($userMap), 'icon' => 'heroicons:pencil-square', 'color' => '#722ED1'],
                    ['key' => 'retention_7', 'title' => 'retention_7', 'value' => $retention, 'icon' => 'heroicons:heart-16-solid', 'color' => '#33D1C9'],
                ],
            ],
            'browsers' => $this->aggregate->topGrouped('user_login_log', 'browser', $last30From, $today, 'login_time', 8),
            'os' => $this->aggregate->topGrouped('user_login_log', 'os', $last30From, $today, 'login_time', 8),
            'hot_routes' => $this->aggregate->topGrouped('user_operation_log', 'service_name', $last30From, $today, 'created_at', 10),
            'items' => [
                [
                    'key' => 'week_login',
                    'title' => 'week_login',
                    'count' => array_sum($weekLoginMap),
                    'growth' => 0,
                    'chart_type' => 'line',
                    'chart' => [
                        'xAxis' => $weekDates,
                        'data' => $this->aggregate->fillDaily($weekLoginMap, $weekDates),
                    ],
                ],
                [
                    'key' => 'week_ops',
                    'title' => 'week_ops',
                    'count' => array_sum($weekOpsMap),
                    'growth' => 0,
                    'chart_type' => 'bar',
                    'chart' => [
                        'xAxis' => $weekDates,
                        'data' => $this->aggregate->fillDaily($weekOpsMap, $weekDates),
                    ],
                ],
                [
                    'key' => 'retention_7',
                    'title' => 'retention_7',
                    'count' => $retention,
                    'growth' => 0,
                    'chart_type' => 'bar',
                    'chart' => [
                        'xAxis' => $weekDates,
                        'data' => $this->aggregate->fillDaily($weekLoginMap, $weekDates),
                    ],
                ],
                [
                    'key' => 'attach_7',
                    'title' => 'attach_7',
                    'count' => array_sum($weekAttachMap),
                    'growth' => 0,
                    'chart_type' => 'line',
                    'chart' => [
                        'xAxis' => $weekDates,
                        'data' => $this->aggregate->fillDaily($weekAttachMap, $weekDates),
                    ],
                ],
            ],
        ];
    }

    private function retentionUsers(Carbon $from, Carbon $to): int
    {
        try {
            $rows = Db::table('user_login_log')
                ->where('status', 1)
                ->where('login_time', '>=', $from->copy()->startOfDay()->toDateTimeString())
                ->where('login_time', '<=', $to->copy()->endOfDay()->toDateTimeString())
                ->selectRaw('username, COUNT(DISTINCT DATE(login_time)) as days')
                ->groupBy('username')
                ->having('days', '>=', 2)
                ->get();

            return $rows->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function platform(): array
    {
        $today = Carbon::today();
        $last30From = $today->copy()->subDays(29);

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($today, $last30From) {
            $tenants = OpsTenant::query()->get();
            $active = $tenants->where('status', FounderTenantService::STATUS_ACTIVE)->count();
            $disabled = $tenants->where('status', FounderTenantService::STATUS_DISABLED)->count();
            $pending = $tenants->whereIn('status', [
                FounderTenantService::STATUS_PROVISIONING,
                FounderTenantService::STATUS_PROVISION_FAILED,
            ])->count();

            $dates = $this->aggregate->dateRange($last30From, $today);
            $createMap = [];
            try {
                $rows = OpsTenant::query()
                    ->where('created_at', '>=', $last30From->copy()->startOfDay())
                    ->where('created_at', '<=', $today->copy()->endOfDay())
                    ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                    ->groupBy('d')
                    ->get();
                foreach ($rows as $row) {
                    $createMap[(string) $row->d] = (int) $row->c;
                }
            } catch (Throwable) {
            }

            $appRanks = [];
            $withDomain = 0;
            $withoutDomain = 0;
            foreach ($tenants as $tenant) {
                $custom = trim((string) ($tenant->custom_domain ?? ''));
                if ($custom !== '') {
                    ++$withDomain;
                } else {
                    ++$withoutDomain;
                }
                $prefix = (string) ($tenant->table_prefix ?: TenantInfo::tablePrefixForId((int) $tenant->id));
                $this->dynamicTablePrefix->apply($prefix);
                $apps = 0;
                try {
                    if (\Hyperf\Database\Schema\Schema::hasTable('tenant_installed_app')) {
                        $apps = (int) Db::table('tenant_installed_app')->where('status', 1)->count();
                    }
                } catch (Throwable) {
                }
                $appRanks[] = [
                    'name' => (string) $tenant->name,
                    'value' => $apps,
                ];
            }
            $this->dynamicTablePrefix->apply('');
            usort($appRanks, static fn ($a, $b) => $b['value'] <=> $a['value']);
            $appRanks = array_slice($appRanks, 0, 10);

            return [
                'overview' => [
                    'dates' => $dates,
                    'series' => [
                        ['key' => 'new_tenants', 'name' => 'new_tenants', 'data' => $this->aggregate->fillDaily($createMap, $dates)],
                    ],
                    'summary' => [
                        ['key' => 'tenants', 'title' => 'tenants', 'value' => $tenants->count(), 'icon' => 'heroicons:user-16-solid', 'color' => '#165DFF'],
                        ['key' => 'active', 'title' => 'active', 'value' => $active, 'icon' => 'heroicons:hand-thumb-up', 'color' => '#33D1C9'],
                        ['key' => 'pending', 'title' => 'pending', 'value' => $pending, 'icon' => 'heroicons:pencil-square', 'color' => '#F77234'],
                        ['key' => 'custom_domain', 'title' => 'custom_domain', 'value' => $withDomain, 'icon' => 'heroicons:heart-16-solid', 'color' => '#722ED1'],
                    ],
                ],
                'browsers' => [],
                'os' => [],
                'hot_routes' => $appRanks,
                'status_pie' => [
                    ['key' => 'active', 'name' => 'active', 'value' => $active],
                    ['key' => 'disabled', 'name' => 'disabled', 'value' => $disabled],
                    ['key' => 'pending', 'name' => 'pending', 'value' => $pending],
                ],
                'domain_pie' => [
                    ['key' => 'bound', 'name' => 'bound', 'value' => $withDomain],
                    ['key' => 'unbound', 'name' => 'unbound', 'value' => $withoutDomain],
                ],
                'items' => [
                    [
                        'key' => 'provision_30',
                        'title' => 'provision_30',
                        'count' => array_sum($createMap),
                        'growth' => 0,
                        'chart_type' => 'line',
                        'chart' => [
                            'xAxis' => $dates,
                            'data' => $this->aggregate->fillDaily($createMap, $dates),
                        ],
                    ],
                    [
                        'key' => 'app_rank',
                        'title' => 'app_rank',
                        'count' => count($appRanks),
                        'growth' => 0,
                        'chart_type' => 'bar',
                        'chart' => [
                            'xAxis' => array_map(static fn ($r) => mb_substr($r['name'], 0, 6), $appRanks),
                            'data' => array_map(static fn ($r) => $r['value'], $appRanks),
                        ],
                    ],
                    [
                        'key' => 'domain_bind',
                        'title' => 'domain_bind',
                        'count' => $withDomain,
                        'growth' => 0,
                        'chart_type' => 'bar',
                        'chart' => [
                            'xAxis' => ['bound', 'unbound'],
                            'data' => [$withDomain, $withoutDomain],
                        ],
                    ],
                    [
                        'key' => 'status_dist',
                        'title' => 'status_dist',
                        'count' => $tenants->count(),
                        'growth' => 0,
                        'chart_type' => 'bar',
                        'chart' => [
                            'xAxis' => ['active', 'disabled', 'pending'],
                            'data' => [$active, $disabled, $pending],
                        ],
                    ],
                ],
            ];
        });
    }
}
