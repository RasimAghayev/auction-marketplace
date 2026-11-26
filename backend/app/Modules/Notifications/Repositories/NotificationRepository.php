<?php

namespace App\Modules\Notifications\Repositories;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository
{
    public function findById(string $id): ?Notification
    {
        return Notification::find($id);
    }

    public function forUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId)->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function unreadCount(string $userId): int
    {
        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update(['read_at' => now()]);

        return $notification;
    }

    public function markAllAsRead(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function create(array $attributes): Notification
    {
        return Notification::create($attributes);
    }
}
