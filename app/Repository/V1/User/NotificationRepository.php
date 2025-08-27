<?php

namespace App\Repository\V1\User;

use App\Interfaces\V1\User\NotificationRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function getUserNotifications(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUnreadNotifications(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    public function markAllAsRead(User $user): bool
    {
        $user->unreadNotifications->markAsRead();
        return true;
    }

    public function getNotification(User $user, string $notificationId): ?DatabaseNotification
    {
        return $user->notifications()
            ->where('id', $notificationId)
            ->first();
    }
    public function getNotificationsCount(User $user)
    {
        return [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
        ];
    }
}
