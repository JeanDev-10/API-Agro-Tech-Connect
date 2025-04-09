<?php

namespace App\Interfaces\V1\User;

use App\Models\V1\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

interface NotificationRepositoryInterface
{
    public function getUserNotifications(User $user, int $perPage = 10): LengthAwarePaginator;
    public function getUnreadNotifications(User $user, int $perPage = 10): LengthAwarePaginator;
    public function markAsRead(User $user, string $notificationId): bool;
    public function markAllAsRead(User $user): bool;
    public function getNotification(User $user, string $notificationId): ?DatabaseNotification;
    public function getNotificationsCount(User $user);
}
