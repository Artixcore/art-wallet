<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Fakes;

use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use RuntimeException;

final class ThrowingPermissionCacheVersionRepository implements PermissionCacheVersionRepository
{
    public function getVersion(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int {
        throw new RuntimeException('simulated version read failure');
    }

    public function bump(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int {
        throw new RuntimeException('simulated version bump failure');
    }
}
