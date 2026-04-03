<?php

namespace App\Policies;

use App\Models\LoginTrustedDevice;
use App\Models\User;

class LoginTrustedDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LoginTrustedDevice $device): bool
    {
        return (int) $device->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, LoginTrustedDevice $device): bool
    {
        return (int) $device->user_id === (int) $user->id;
    }
}
