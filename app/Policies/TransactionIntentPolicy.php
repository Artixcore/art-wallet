<?php

namespace App\Policies;

use App\Models\TransactionIntent;
use App\Models\User;

class TransactionIntentPolicy
{
    public function view(User $user, TransactionIntent $transactionIntent): bool
    {
        return (int) $user->id === (int) $transactionIntent->user_id;
    }

    public function broadcast(User $user, TransactionIntent $transactionIntent): bool
    {
        return $this->view($user, $transactionIntent);
    }
}
