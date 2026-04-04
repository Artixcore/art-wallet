<?php

namespace App\Policies;

use App\Models\AgentApiCredential;
use App\Models\User;

class AgentApiCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AgentApiCredential $credential): bool
    {
        return (int) $credential->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, AgentApiCredential $credential): bool
    {
        return $this->view($user, $credential);
    }
}
