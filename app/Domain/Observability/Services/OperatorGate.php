<?php

declare(strict_types=1);

namespace App\Domain\Observability\Services;

use App\Models\User;
use Artixcore\ArtGate\Facades\ArtGate;

/**
 * Operator RBAC: `is_admin` bootstrap + ArtGate permissions from config/observability.php.
 */
final class OperatorGate
{
    /**
     * @param  string  $abilityKey  Key under `observability.permissions` (e.g. dashboard, health).
     */
    public function allows(User $user, string $abilityKey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permission = config('observability.permissions.'.$abilityKey);
        if (! is_string($permission) || $permission === '') {
            return false;
        }

        return ArtGate::check($permission);
    }
}
