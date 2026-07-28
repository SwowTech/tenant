<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Tenant\TenantProvisionService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

#[Command(name: 'tenant:provision', description: 'Provision tenant tables with cy_{id}_ prefix')]
final class TenantProvisionCommand extends HyperfCommand
{
    public function __construct(private readonly TenantProvisionService $provisionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = (int) $this->input->getArgument('id');
        if ($id <= 0) {
            $this->error('Invalid tenant id');
            return self::FAILURE;
        }
        $adminUser = (string) ($this->input->getOption('admin-user') ?: 'admin');
        $adminPass = (string) ($this->input->getOption('admin-pass') ?: '123456');

        $this->info(sprintf('Provisioning tenant %d with prefix cy_%d_', $id, $id));
        $this->provisionService->provision($id, $adminUser, $adminPass);
        $this->info('Tenant provision completed.');
        return self::SUCCESS;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('id', null, 'Tenant id from platform_db');
        $this->addOption('admin-user', null, null, 'Tenant admin username');
        $this->addOption('admin-pass', null, null, 'Tenant admin password');
    }
}
