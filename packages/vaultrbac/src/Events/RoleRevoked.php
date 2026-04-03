<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Events;

use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RoleRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $model,
        public Role $role,
        public string|int $tenantId,
        public string|int|null $teamId,
    ) {}
}
