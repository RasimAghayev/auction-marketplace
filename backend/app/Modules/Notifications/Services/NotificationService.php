<?php

namespace App\Modules\Notifications\Services;

use App\Models\Notification;
use App\Modules\Notifications\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        protected NotificationRepository $repository
    ) {}

    public function forUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->forUser($userId, $filters);
    }

    public function unreadCount(string $userId): int
    {
        return $this->repository->unreadCount($userId);
    }

    public function markAsRead(Notification $notification): Notification
    {
        return $this->repository->markAsRead($notification);
    }

    public function markAllAsRead(string $userId): int
    {
        return $this->repository->markAllAsRead($userId);
    }
}
