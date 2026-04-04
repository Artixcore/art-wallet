<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agent $agent): bool
    {
        return (int) $agent->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Agent $agent): bool
    {
        return $this->view($user, $agent);
    }

    public function delete(User $user, Agent $agent): bool
    {
        return $this->view($user, $agent);
    }

    public function run(User $user, Agent $agent): bool
    {
        return $this->view($user, $agent);
    }

    public function manageCredentials(User $user): bool
    {
        return true;
    }
}
