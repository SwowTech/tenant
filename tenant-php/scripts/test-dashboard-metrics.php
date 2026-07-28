<?php

declare(strict_types=1);

/**
 * Smoke: Dashboard analysis/report services.
 * Usage: php scripts/test-dashboard-metrics.php
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);

require BASE_PATH . '/vendor/autoload.php';

use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantInfo;
use App\Model\OpsTenant;
use App\Service\Dashboard\DashboardAggregate;
use App\Service\Dashboard\DashboardAnalysisService;
use App\Service\Dashboard\DashboardReportService;
use App\Service\Dashboard\DashboardScopeResolver;
use App\Service\Welcome\WelcomeChartService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\ScanHandler\ProcScanHandler;
use Hyperf\Engine\DefaultOption;

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

$bootstrap = BASE_PATH . '/tests/bootstrap.php';
if (is_file($bootstrap)) {
    require $bootstrap;
} else {
    ClassLoader::init(handler: new ProcScanHandler());
    require BASE_PATH . '/config/container.php';
}

$container = ApplicationContext::getContainer();
/** @var DynamicTablePrefix $prefix */
$prefix = $container->get(DynamicTablePrefix::class);
$agg = $container->get(DashboardAggregate::class);

$prefix->reset();
$chart = new WelcomeChartService($agg);
$series = $chart->series('realtime', date('Y-m-d', strtotime('-6 days')), date('Y-m-d'));
if (! isset($series['dates'], $series['visits'], $series['visitors'])) {
    fwrite(STDERR, "FAIL chart shape\n");
    exit(1);
}
echo 'OK welcome chart days=' . count($series['dates']) . ' visits_sum=' . array_sum($series['visits']) . "\n";

$fakeResolver = new class extends DashboardScopeResolver {
    private string $force = DashboardScopeResolver::SCOPE_PLATFORM;

    public function __construct() {}

    public function force(string $scope): void
    {
        $this->force = $scope;
    }

    public function resolve(): array
    {
        $labels = match ($this->force) {
            self::SCOPE_TENANT => ['page_title' => '租户运营概览'],
            self::SCOPE_SITE => ['page_title' => '本站概览'],
            default => ['page_title' => '平台总览'],
        };

        return ['scope' => $this->force, 'labels' => $labels];
    }
};

$analysis = new DashboardAnalysisService($fakeResolver, $agg, $prefix);
$report = new DashboardReportService($fakeResolver, $agg, $prefix);

$fakeResolver->force(DashboardScopeResolver::SCOPE_PLATFORM);
$platform = $analysis->get();
if (($platform['scope'] ?? '') !== 'platform' || empty($platform['kpis'])) {
    fwrite(STDERR, "FAIL platform analysis\n");
    exit(1);
}
echo 'OK platform analysis kpis=' . count($platform['kpis']) . ' tenants_kpi=' . ($platform['kpis'][0]['count'] ?? 0) . "\n";

$fakeResolver->force(DashboardScopeResolver::SCOPE_SITE);
$site = $analysis->get();
if (($site['scope'] ?? '') !== 'site') {
    fwrite(STDERR, "FAIL site analysis\n");
    exit(1);
}
echo 'OK site analysis users=' . ($site['kpis'][0]['count'] ?? 0) . ' logins7=' . ($site['kpis'][1]['count'] ?? 0) . "\n";

$row = $prefix->withoutPrefix(static fn () => OpsTenant::query()->where('status', 1)->orderBy('id')->first());
if ($row) {
    $info = new TenantInfo(
        id: (int) $row->id,
        code: (string) $row->code,
        domain: (string) $row->domain,
        tablePrefix: (string) ($row->table_prefix ?: TenantInfo::tablePrefixForId((int) $row->id)),
        status: (int) $row->status,
    );
    TenantContext::set($info);
    $prefix->apply($info->tablePrefix);
    $fakeResolver->force(DashboardScopeResolver::SCOPE_TENANT);
    $tenant = $analysis->get();
    $tReport = $report->get();
    echo 'OK tenant analysis id=' . $info->id . ' users=' . ($tenant['kpis'][0]['count'] ?? 0) . "\n";
    echo 'OK tenant report series=' . count($tReport['overview']['series'] ?? []) . "\n";
    TenantContext::clear();
    $prefix->reset();
} else {
    echo "SKIP tenant (no active ops_tenant)\n";
}

$fakeResolver->force(DashboardScopeResolver::SCOPE_PLATFORM);
$pReport = $report->get();
echo 'OK platform report items=' . count($pReport['items'] ?? []) . "\n";
echo "PASS dashboard metrics smoke\n";
