<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Welcome;

use App\Service\Cloud\CloudSiteSettingService;
use App\Service\Welcome\SaasBindService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SaasBindServiceTest extends TestCase
{
    public function testStatusWhenCloudSiteUnbound(): void
    {
        $cloud = $this->cloudSiteStub(false, []);
        $result = (new SaasBindService($cloud))->status();

        self::assertFalse($result['bound']);
        self::assertNull($result['site']);
        self::assertNull($result['tenant']);
        self::assertSame('您还未注册云站点（可选）。注册后可使用云升级、应用订阅等能力', $result['message']);
        self::assertSame('/setting/cloud/register', $result['bind_url']);
    }

    public function testStatusWhenCloudSiteBound(): void
    {
        $cloud = $this->cloudSiteStub(true, [
            'key' => 'site-key-1',
            'token' => 'secret',
            'url' => 'http://127.0.0.1:9501',
            'username' => '13800138000',
            'phone' => '138****8000',
            'email' => 'a***@ex.com',
            'bound_at' => '2026-07-17T00:00:00+08:00',
        ]);
        $result = (new SaasBindService($cloud))->status();

        self::assertTrue($result['bound']);
        self::assertNull($result['tenant']);
        self::assertSame('已注册云站点', $result['message']);
        self::assertSame('/setting/cloud/register', $result['bind_url']);
        self::assertSame('site-key-1', $result['site']['key']);
        self::assertSame('13800138000', $result['site']['username']);
        self::assertArrayNotHasKey('token', $result['site']);
    }

    /**
     * @param array<string, string> $site
     */
    private function cloudSiteStub(bool $bound, array $site): CloudSiteSettingService
    {
        return new class($bound, $site) extends CloudSiteSettingService {
            public function __construct(
                private readonly bool $boundFlag,
                private readonly array $siteData,
            ) {
                // Skip parent ctor — override methods only.
            }

            public function isBound(): bool
            {
                return $this->boundFlag;
            }

            public function getSite(): array
            {
                return array_merge([
                    'key' => '',
                    'token' => '',
                    'url' => '',
                    'username' => '',
                    'phone' => '',
                    'email' => '',
                    'bound_at' => '',
                ], $this->siteData);
            }
        };
    }
}
