<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Enums\ApprovalStatus;
use Artwallet\VaultRbac\Models\ApprovalRequest;
use Illuminate\Console\Command;

final class ApprovalListCommand extends Command
{
    protected $signature = 'vaultrbac:approval:list
                            {--tenant= : Filter by tenant id}
                            {--pending : Only pending requests}';

    protected $description = 'List approval requests';

    public function handle(): int
    {
        $q = ApprovalRequest::query()->orderByDesc('id');
        if ((bool) $this->option('pending')) {
            $q->where('status', ApprovalStatus::Pending);
        }
        if ($this->option('tenant') !== null) {
            $q->where('tenant_id', $this->option('tenant'));
        }

        $rows = $q->limit(50)->get();
        if ($rows->isEmpty()) {
            $this->info('No approval requests found.');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $this->line(sprintf(
                'id=%s tenant=%s status=%s correlation=%s',
                $row->getKey(),
                $row->tenant_id,
                $row->status->value,
                $row->correlation_id,
            ));
        }

        return self::SUCCESS;
    }
}
