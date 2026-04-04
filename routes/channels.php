<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('user.{userId}.wallet.{walletId}', function ($user, $userId, $walletId) {
    if ((int) $user->id !== (int) $userId) {
        return false;
    }

    return $user->wallets()->whereKey($walletId)->exists();
});
