<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSessionRecord;

class UserSessionRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function revoke(User $user, UserSessionRecord $record): bool
    {
        return (int) $record->user_id === (int) $user->id;
    }
}
