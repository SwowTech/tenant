<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Service\App;

use App\Exception\BusinessException;
use App\Service\App\AppDataMigrateService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppDataMigrateServiceTest extends TestCase
{
    private string $appDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->appDir = sys_get_temp_dir() . '/app-migrate-test-' . uniqid('', true);
        mkdir($this->appDir, 0777, true);
        mkdir($this->appDir . '/migrate', 0777, true);
        file_put_contents($this->appDir . '/migrate/ok.sql', "-- ok\n");
    }

    protected function tearDown(): void
    {
        if ($this->appDir !== '' && is_dir($this->appDir)) {
            @unlink($this->appDir . '/migrate/ok.sql');
            @unlink($this->appDir . '/migrate/evil.php');
            @rmdir($this->appDir . '/migrate');
            @rmdir($this->appDir);
        }
        parent::tearDown();
    }

    public function testResolveMigrateSqlPathAcceptsValidSql(): void
    {
        $path = AppDataMigrateService::resolveMigrateSqlPath($this->appDir, 'migrate/ok.sql');
        self::assertSame(realpath($this->appDir . '/migrate/ok.sql'), $path);
    }

    public function testResolveMigrateSqlPathRejectsParentTraversal(): void
    {
        $this->expectException(BusinessException::class);
        AppDataMigrateService::resolveMigrateSqlPath($this->appDir, '../outside.sql');
    }

    public function testResolveMigrateSqlPathRejectsNonSqlExtension(): void
    {
        file_put_contents($this->appDir . '/migrate/evil.php', '<?php');
        $this->expectException(BusinessException::class);
        AppDataMigrateService::resolveMigrateSqlPath($this->appDir, 'migrate/evil.php');
    }

    public function testResolveMigrateSqlPathRejectsMissingFile(): void
    {
        $this->expectException(BusinessException::class);
        AppDataMigrateService::resolveMigrateSqlPath($this->appDir, 'migrate/missing.sql');
    }
}
