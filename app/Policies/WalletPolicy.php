<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return (int) $wallet->user_id === (int) $user->id;
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }

    public function createTransactionIntent(User $user, Wallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }
}
