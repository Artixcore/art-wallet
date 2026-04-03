<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

use Artwallet\VaultRbac\Models\ApprovalRequest;

/**
 * Output-only: privileged change queued for approval.
 */
final readonly class ApprovalSubmissionResult
{
    public function __construct(
        public ApprovalRequest $request,
    ) {}
}
