<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use App\Http\CurrentUser;
use App\Library\Auth\FounderGate;
use App\Library\Tenant\TenantContext;

class DashboardScopeResolver
{
    public const SCOPE_TENANT = 'tenant';

    public const SCOPE_PLATFORM = 'platform';

    public const SCOPE_SITE = 'site';

    public function __construct(
        private readonly CurrentUser $currentUser,
    ) {}

    /**
     * @return array{scope: string, labels: array<string, string>}
     */
    public function resolve(): array
    {
        if (TenantContext::get() !== null) {
            return [
                'scope' => self::SCOPE_TENANT,
                'labels' => $this->labels(self::SCOPE_TENANT),
            ];
        }

        if ($this->isFounder()) {
            return [
                'scope' => self::SCOPE_PLATFORM,
                'labels' => $this->labels(self::SCOPE_PLATFORM),
            ];
        }

        return [
            'scope' => self::SCOPE_SITE,
            'labels' => $this->labels(self::SCOPE_SITE),
        ];
    }

    public function isFounder(): bool
    {
        return FounderGate::allows($this->currentUser->id(), $this->currentUser->user());
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $scope): array
    {
        return match ($scope) {
            self::SCOPE_PLATFORM => [
                'page_title' => 'page_title',
                'report_title' => 'report_title',
                'trend_title' => 'trend_title',
                'ranking_title' => 'ranking_title',
                'pie_title' => 'pie_title',
            ],
            self::SCOPE_TENANT => [
                'page_title' => 'page_title',
                'report_title' => 'report_title',
                'trend_title' => 'trend_title',
                'ranking_title' => 'ranking_title',
                'pie_title' => 'pie_title',
            ],
            default => [
                'page_title' => 'page_title',
                'report_title' => 'report_title',
                'trend_title' => 'trend_title',
                'ranking_title' => 'ranking_title',
                'pie_title' => 'pie_title',
            ],
        };
    }
}
