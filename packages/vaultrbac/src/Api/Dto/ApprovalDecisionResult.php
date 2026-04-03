<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

/**
 * Output-only: outcome of approve/reject on an approval request.
 */
final readonly class ApprovalDecisionResult
{
    public function __construct(
        public string|int $approvalRequestId,
        public bool $approved,
    ) {}
}
