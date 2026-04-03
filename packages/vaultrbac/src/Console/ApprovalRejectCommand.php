<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Illuminate\Console\Command;

final class ApprovalRejectCommand extends Command
{
    protected $signature = 'vaultrbac:approval:reject
                            {id : Approval request id}
                            {approver : Approver user id}';

    protected $description = 'Reject a pending VaultRBAC approval request';

    public function handle(ApprovalWorkflowInterface $workflow): int
    {
        $id = $this->argument('id');
        $approver = $this->argument('approver');
        try {
            $workflow->reject($id, $approver);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info('Rejected.');

        return self::SUCCESS;
    }
}
