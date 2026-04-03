<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Exceptions\InvalidAssignmentException;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

/**
 * @group integration
 */
final class ApprovalWorkflowServiceIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_approve_applies_role_assignment(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $subject = $this->createUser();
        $approver = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'approved-role');

        $submission = $this->vault()->submitApproval(
            $subject,
            $role,
            $tenant->getKey(),
            (int) $approver->getKey(),
        );

        self::assertSame(0, ModelRole::query()->where('model_id', $subject->getKey())->count());

        $this->vault()->approve($submission->request->getKey(), (int) $approver->getKey());

        self::assertSame(1, ModelRole::query()->where('model_id', $subject->getKey())->count());
    }

    public function test_second_approve_throws_and_leaves_single_assignment(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $subject = $this->createUser();
        $approver = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'once');

        $submission = $this->vault()->submitApproval(
            $subject,
            $role,
            $tenant->getKey(),
            (int) $approver->getKey(),
        );

        $this->vault()->approve($submission->request->getKey(), (int) $approver->getKey());

        self::assertSame(1, ModelRole::query()->where('model_id', $subject->getKey())->count());

        $this->expectException(InvalidAssignmentException::class);
        $this->vault()->approve($submission->request->getKey(), (int) $approver->getKey());
    }
}
