<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Library\App;

use App\Library\App\AppEdition;
use App\Library\App\AppManifest;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppEditionTest extends TestCase
{
    public function testEditionFromManifestNormalizes(): void
    {
        self::assertSame('pro', AppEdition::editionFromManifest(['edition' => 'PRO']));
    }

    public function testEditionFromManifestEmptyWhenMissing(): void
    {
        self::assertSame('', AppEdition::editionFromManifest([]));
    }

    public function testFamilyFromManifestUsesFamilyWhenPresent(): void
    {
        self::assertSame(
            'demo',
            AppEdition::familyFromManifest(['family' => 'demo'], 'mineadmin/demo-pro'),
        );
    }

    public function testFamilyFromManifestDefaultsToIdentifier(): void
    {
        self::assertSame(
            'mineadmin/demo',
            AppEdition::familyFromManifest([], 'mineadmin/demo'),
        );
    }

    public function testUpgradesFromFromManifestFiltersEmptyAndDedupes(): void
    {
        self::assertSame(
            ['a', 'b'],
            AppEdition::upgradesFromFromManifest(['upgrades_from' => ['a', '', 'b']]),
        );
    }

    public function testMigrateRelativePathReturnsNullWhenMissing(): void
    {
        self::assertNull(AppEdition::migrateRelativePath([]));
    }

    public function testMigrateRelativePathReturnsTrimmedString(): void
    {
        self::assertSame('bin/migrate.php', AppEdition::migrateRelativePath(['migrate' => '  bin/migrate.php  ']));
    }

    public function testShouldAutoAssignWhenEditionMissingOrCommunity(): void
    {
        self::assertTrue(AppEdition::shouldAutoAssign([]));
        self::assertTrue(AppEdition::shouldAutoAssign(['edition' => 'community']));
        self::assertFalse(AppEdition::shouldAutoAssign(['edition' => 'pro']));
    }

    public function testEditionMetaForDemoApp(): void
    {
        $meta = AppManifest::editionMeta('mineadmin/demo');
        self::assertIsArray($meta);
        self::assertArrayHasKey('edition', $meta);
        self::assertArrayHasKey('family', $meta);
        self::assertArrayHasKey('upgrades_from', $meta);
        self::assertArrayHasKey('migrate', $meta);
        self::assertArrayHasKey('pricing', $meta);
        self::assertSame('mineadmin/demo', $meta['family']);
    }

    public function testFillEditionFieldsUsesManifestWhenPresent(): void
    {
        $payload = [];
        AppEdition::fillEditionFields($payload, 'mineadmin/demo');
        self::assertSame('mineadmin/demo', $payload['family']);
        self::assertArrayHasKey('edition', $payload);
    }

    public function testFillEditionFieldsFallbackWhenPackageMissing(): void
    {
        $payload = [];
        AppEdition::fillEditionFields($payload, 'nonexistent/vendor-app');
        self::assertSame('', $payload['edition']);
        self::assertSame('nonexistent/vendor-app', $payload['family']);
    }
}
