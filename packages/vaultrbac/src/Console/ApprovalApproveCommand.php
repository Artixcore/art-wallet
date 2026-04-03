<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Illuminate\Console\Command;

final class ApprovalApproveCommand extends Command
{
    protected $signature = 'vaultrbac:approval:approve
                            {id : Approval request id}
                            {approver : Approver user id}';

    protected $description = 'Approve a pending VaultRBAC approval request';

    public function handle(ApprovalWorkflowInterface $workflow): int
    {
        $id = $this->argument('id');
        $approver = $this->argument('approver');
        try {
            $workflow->approve($id, $approver);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info('Approved.');

        return self::SUCCESS;
    }
}
