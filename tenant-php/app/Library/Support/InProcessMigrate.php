<?php

declare(strict_types=1);

namespace App\Library\Support;

use Hyperf\Context\Context;
use Hyperf\Database\Migrations\MigrationRepositoryInterface;
use Hyperf\Database\Migrations\Migrator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Run pending migrations inside an HTTP/worker process.
 *
 * Do NOT use MigrateCommand here: Hyperf Command::execute() resumes WORKER_EXIT
 * when Application auto-exit is on, which cleanly stops `php bin/hyperf.php start`.
 */
final class InProcessMigrate
{
    /**
     * @param  array<string, mixed>  $input  unused options kept for call-site compatibility
     */
    public static function run(
        ContainerInterface $container,
        array $input = [],
        ?OutputInterface $output = null,
    ): int {
        /** @var Migrator $migrator */
        $migrator = $container->get(Migrator::class);

        $connection = 'default';
        $database = $input['--database'] ?? null;
        if (is_string($database) && $database !== '') {
            $connection = $database;
        }

        // 丢掉协程内旧连接，避免动态前缀下 hasTable / create 读到不同 prefix
        Context::set(sprintf('database.connection.%s', $connection), null);

        $migrator->setConnection($connection);
        self::ensureRepository($container, $migrator, $connection);

        if ($output !== null) {
            $migrator->setOutput($output);
        }

        // Same paths as Hyperf MigrateCommand::getMigrationPaths()
        $paths = array_merge(
            $migrator->paths(),
            [BASE_PATH . DIRECTORY_SEPARATOR . 'migrations'],
        );

        $migrator->run($paths, [
            'pretend' => ! empty($input['--pretend']),
            'step' => ! empty($input['--step']),
        ]);

        return 0;
    }

    private static function ensureRepository(
        ContainerInterface $container,
        Migrator $migrator,
        string $connection,
    ): void {
        if ($migrator->repositoryExists()) {
            return;
        }

        try {
            /** @var MigrationRepositoryInterface $repository */
            $repository = $container->get(MigrationRepositoryInterface::class);
            $repository->setSource($connection);
            $repository->createRepository();
        } catch (Throwable $e) {
            // 上次开通中断后表已在；动态前缀下 repositoryExists 偶发误判
            if (self::isTableAlreadyExists($e)) {
                return;
            }
            throw $e;
        }
    }

    private static function isTableAlreadyExists(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '42S01')
            || str_contains($message, 'already exists');
    }
}
