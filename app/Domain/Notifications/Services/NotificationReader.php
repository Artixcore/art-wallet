<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Models\InAppNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class NotificationReader
{
    public function unreadCount(User $user): int
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->active()
            ->count();
    }

    /**
     * @return Collection<int, InAppNotification>
     */
    public function latestDropdown(User $user, int $limit = 10): Collection
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<InAppNotification>
     */
    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findForUser(User $user, int $id): ?InAppNotification
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->first();
    }
}
