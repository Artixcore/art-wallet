<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return (int) $user->id === (int) $wallet->user_id;
    }

    public function manageAddresses(User $user, Wallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }

    public function createTransactionIntent(User $user, Wallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }
}
