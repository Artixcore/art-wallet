<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Events;

use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PermissionGranted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $model,
        public Permission $permission,
        public string|int $tenantId,
        public string|int|null $teamId,
        public string $effect,
        public ModelPermission $assignment,
    ) {}
}
